<?php

namespace SymfonyTools\CodeBlockChecker\Service\CodeValidator;

use phpDocumentor\Guides\Nodes\CodeNode;
use PhpParser\ErrorHandler;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use SymfonyTools\CodeBlockChecker\Issue\Issue;
use SymfonyTools\CodeBlockChecker\Issue\IssueCollection;
use SymfonyTools\CodeBlockChecker\Listener\DetailedCodeNode;

class PhpValidator implements Validator
{
    private ?Parser $parser = null;

    public function validate(DetailedCodeNode $node, IssueCollection $issues): void
    {
        $codeNode = $node->codeNode;
        $language = $codeNode->getLanguage() ?? 'php';
        if (!in_array($language, ['php', 'php-symfony', 'php-standalone', 'php-annotations', 'html+php'])) {
            return;
        }

        $linesPrepended = 0;
        $code = 'html+php' === $language ? $codeNode->getValue() : $this->getContents($codeNode, $linesPrepended);
        $this->getParser()->parse($code, $errorHandler = new ErrorHandler\Collecting());

        foreach ($errorHandler->getErrors() as $error) {
            $issues->addIssue(new Issue($codeNode, $error->getRawMessage(), 'PHP syntax', $node->fileName, $error->getStartLine() - $linesPrepended));
        }
    }

    private function getParser(): Parser
    {
        if (null === $this->parser) {
            $this->parser = (new ParserFactory())->createForNewestSupportedVersion();
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

        // Allow us to use "= /* some placeholder */;" as a placeholder value
        $contents = preg_replace('#=[ \t]*/\*.*?\*/[ \t]*;#', '= null;', $contents);

        $lines = explode("\n", $contents);
        if (!str_contains($lines[0], '<?php') && !str_contains($lines[1] ?? '', '<?php') && !str_contains($lines[2] ?? '', '<?php')) {
            $contents = '<?php'."\n".$contents;
            $linesPrepended = 1;
        }

        return $contents;
    }
}
