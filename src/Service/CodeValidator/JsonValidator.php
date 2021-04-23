<?php

namespace SymfonyTools\CodeBlockChecker\Service\CodeValidator;

use Doctrine\RST\Nodes\CodeNode;
use SymfonyTools\CodeBlockChecker\Issue\Issue;
use SymfonyTools\CodeBlockChecker\Issue\IssueCollection;

class JsonValidator implements Validator
{
    public function validate(CodeNode $node, IssueCollection $issues): void
    {
        if ('json' !== $node->getLanguage()) {
            return;
        }

        try {
            $data = json_decode($node->getValue(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $issues->addIssue(new Issue($node, $e->getMessage(), 'JSON syntax', $node->getEnvironment()->getCurrentFileName(), 0));
        }
    }
}
