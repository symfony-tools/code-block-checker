<?php

namespace SymfonyTools\CodeBlockChecker\Service\CodeValidator;

use Doctrine\RST\Nodes\CodeNode;
use SymfonyTools\CodeBlockChecker\Issue\IssueCollection;

interface Validator
{
    public function validate(CodeNode $node, IssueCollection $issues): void;
}
