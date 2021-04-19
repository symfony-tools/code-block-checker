<?php

namespace Symfony\CodeBlockChecker\Service\CodeValidator;

use Doctrine\RST\Nodes\CodeNode;
use Symfony\CodeBlockChecker\Issue\Issue;
use Symfony\CodeBlockChecker\Issue\IssueCollection;
use Symfony\CodeBlockChecker\Twig\DummyExtension;
use Twig\Environment;
use Twig\Error\SyntaxError;
use Twig\Loader\ArrayLoader;
use Twig\Source;

class TwigValidator implements Validator
{
    private $twig;

    public function validate(CodeNode $node, IssueCollection $issues): void
    {
        if (!in_array($node->getLanguage(), ['twig', 'html+twig'])) {
            return;
        }

        if (null === $this->twig) {
            $this->twig = new Environment(new ArrayLoader());
            $this->twig->addExtension(new DummyExtension());
        }

        try {
            $tokens = $this->twig->tokenize(new Source($node->getValue(), $node->getEnvironment()->getCurrentFileName()));
            // We cannot parse the TokenStream because we dont have all extensions loaded.
            $this->twig->parse($tokens);
        } catch (SyntaxError $e) {
            $issues->addIssue(new Issue($node, $e->getMessage(), 'Invalid syntax', $node->getEnvironment()->getCurrentFileName(), 0));
        }
    }
}
