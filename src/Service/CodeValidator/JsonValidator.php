<?php

namespace SymfonyTools\CodeBlockChecker\Service\CodeValidator;

use SymfonyTools\CodeBlockChecker\Issue\Issue;
use SymfonyTools\CodeBlockChecker\Issue\IssueCollection;
use SymfonyTools\CodeBlockChecker\Listener\DetailedCodeNode;

class JsonValidator implements Validator
{
    public function validate(DetailedCodeNode $node, IssueCollection $issues): void
    {
        $codeNode = $node->codeNode;
        if ('json' !== $codeNode->getLanguage()) {
            return;
        }

        try {
            $data = json_decode($codeNode->getValue(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $issues->addIssue(new Issue($codeNode, $e->getMessage(), 'JSON syntax', $node->fileName, 0));
        }
    }
}
