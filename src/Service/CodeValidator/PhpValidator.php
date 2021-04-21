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
        if (!in_array($language, ['php', 'php-symfony', 'php-standalone', 'php-annotations', 'html+php'])) {
            return;
        }

        $file = sys_get_temp_dir().'/'.uniqid('doc_builder', true).'.php';

        file_put_contents($file, $this->getContents($node, $language));

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
        $issues->addIssue(new Issue($node, $text, 'PHP syntax', $node->getEnvironment()->getCurrentFileName(), $line));
    }

    private function getContents(CodeNode $node, string $language): string
    {
        $contents = $node->getValue();
        if ('html+php' === $language) {
            return $contents;
        }

        if (!preg_match('#(class|interface) [a-zA-Z]+#s', $contents) && preg_match('#(public|protected|private)( static)? (\$[a-z]+|function)#s', $contents)) {
            $contents = 'class Foobar {'.$contents.'}';
        }

        // Allow us to use "..." as a placeholder
        $contents = str_replace('...', 'null', $contents);

        $lines = explode(PHP_EOL, $contents);
        if (!str_contains($lines[0] ?? '', '<?php') && !str_contains($lines[1] ?? '', '<?php') && !str_contains($lines[2] ?? '', '<?php')) {
            $contents = '<?php'.PHP_EOL.$contents;
        }

        return $contents;
    }
}
