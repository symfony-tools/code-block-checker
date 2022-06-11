<?php

namespace SymfonyTools\CodeBlockChecker\Service\CodeValidator;

use Doctrine\RST\Nodes\CodeNode;
use PhpParser\ErrorHandler;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use SymfonyTools\CodeBlockChecker\Issue\Issue;
use SymfonyTools\CodeBlockChecker\Issue\IssueCollection;

class PhpValidator implements Validator
{
    private ?Parser $parser = null;

    public function validate(CodeNode $node, IssueCollection $issues): void
    {
        $language = $node->getLanguage() ?? ($node->isRaw() ? null : 'php');
        if (!in_array($language, ['php', 'php-symfony', 'php-standalone', 'php-annotations', 'html+php'])) {
            return;
        }

        $linesPrepended = 0;
        $code = 'html+php' === $language ? $node->getValue() : $this->getContents($node, $linesPrepended);
        $this->getParser()->parse($code, $errorHandler = new ErrorHandler\Collecting());

        foreach ($errorHandler->getErrors() as $error) {
            $issues->addIssue(new Issue($node, $error->getRawMessage(), 'PHP syntax', $node->getEnvironment()->getCurrentFileName(), $error->getStartLine() - $linesPrepended));
        }
    }

    private function getParser(): Parser
    {
        if (null === $this->parser) {
            $this->parser = (new ParserFactory())->create(ParserFactory::ONLY_PHP7);
        }

        return $this->parser;
    }

    private function getContents(CodeNode $node, &$linesPrepended = null): string
    {
        $contents = $node->getValue();
        if (
            !preg_match('#(class|interface) [a-zA-Z]+#s', $contents)
            && !preg_match('#= new class#s', $contents)
            && preg_match('#(public|protected|private)( static)? (\$[a-z]+|function).*#s', $contents, $matches)
        ) {
            // keep "uses" and other code before the class definition
            $contents = substr($contents, 0, strpos($contents, $matches[1])).PHP_EOL.'class Foobar {'.$matches[0].'}';
        }

        // Allow us to use "..." as a placeholder
        $contents = str_replace(['...,', '...)', '...;', '...]', '... }'], ['null,', 'null)', 'null;', 'null]', '$a = null; }'], $contents);

        $lines = explode("\n", $contents);
        if (!str_contains($lines[0] ?? '', '<?php') && !str_contains($lines[1] ?? '', '<?php') && !str_contains($lines[2] ?? '', '<?php')) {
            $contents = '<?php'."\n".$contents;
            $linesPrepended = 1;
        }

        return $contents;
    }
}
