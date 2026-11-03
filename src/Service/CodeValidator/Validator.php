<?php

namespace SymfonyTools\CodeBlockChecker\Service\CodeValidator;

use SymfonyTools\CodeBlockChecker\Issue\IssueCollection;
use SymfonyTools\CodeBlockChecker\Listener\DetailedCodeNode;

interface Validator
{
    public function validate(DetailedCodeNode $node, IssueCollection $issues): void;
}
