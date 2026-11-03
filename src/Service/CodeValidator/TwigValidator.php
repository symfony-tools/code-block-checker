<?php

namespace SymfonyTools\CodeBlockChecker\Service\CodeValidator;

use SymfonyTools\CodeBlockChecker\Issue\Issue;
use SymfonyTools\CodeBlockChecker\Issue\IssueCollection;
use SymfonyTools\CodeBlockChecker\Listener\DetailedCodeNode;
use SymfonyTools\CodeBlockChecker\Twig\DummyExtension;
use Twig\Environment;
use Twig\Error\SyntaxError;
use Twig\Loader\ArrayLoader;
use Twig\Source;

class TwigValidator implements Validator
{
    private $twig;

    public function validate(DetailedCodeNode $node, IssueCollection $issues): void
    {
        $codeNode = $node->codeNode;
        if (!in_array($codeNode->getLanguage(), ['twig', 'html+twig'])) {
            return;
        }

        if (null === $this->twig) {
            $this->twig = new Environment(new ArrayLoader());
            $this->twig->addExtension(new DummyExtension());
        }

        try {
            $tokens = $this->twig->tokenize(new Source($codeNode->getValue(), $node->fileName));
            // We cannot parse the TokenStream because we dont have all extensions loaded.
            $this->twig->parse($tokens);
        } catch (SyntaxError $e) {
            $issues->addIssue(new Issue($codeNode, $e->getRawMessage(), 'Twig', $node->fileName, $e->getTemplateLine()));
        }
    }
}
