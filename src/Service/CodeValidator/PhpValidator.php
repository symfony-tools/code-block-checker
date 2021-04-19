<?php

namespace Symfony\CodeBlockChecker\Service\CodeValidator;

use Doctrine\RST\Nodes\CodeNode;
use Symfony\CodeBlockChecker\Issue\Issue;
use Symfony\CodeBlockChecker\Issue\IssueCollection;
use Symfony\Component\Process\Process;

class PhpValidator implements Validator
{
    public function validate(CodeNode $node, IssueCollection $issues): void
    {
        $language = $node->getLanguage() ?? ($node->isRaw() ? null : 'php');
        if (!in_array($language, ['php', 'php-symfony', 'php-standalone', 'php-annotations'])) {
            return;
        }

        $file = sys_get_temp_dir().'/'.uniqid('doc_builder', true).'.php';
        $contents = $node->getValue();
        if (!preg_match('#class [a-zA-Z]+#s', $contents) && preg_match('#(public|protected|private) (\$[a-z]+|function)#s', $contents)) {
            $contents = 'class Foobar {'.$contents.'}';
        }

        // Allow us to use "..." as a placeholder
        $contents = str_replace('...', 'null', $contents);
        file_put_contents($file, '<?php'.PHP_EOL.$contents);

        $process = new Process(['php', '-l', $file]);
        $process->run();
        if ($process->isSuccessful()) {
            return;
        }

        $line = 0;
        $text = str_replace($file, 'example.php', $process->getErrorOutput());
        if (preg_match('| in example.php on line ([0-9]+)|s', $text, $matches)) {
            $text = str_replace($matches[0], '', $text);
            $line = ((int) $matches[1]) - 1; // we added "<?php"
        }
        $issues->addIssue(new Issue($node, $text, 'Invalid syntax', $node->getEnvironment()->getCurrentFileName(), $line));
    }
}
