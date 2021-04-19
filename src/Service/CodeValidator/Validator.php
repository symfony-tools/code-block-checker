<?php

namespace Symfony\CodeBlockChecker\Service\CodeValidator;

use Doctrine\RST\Nodes\CodeNode;
use Symfony\CodeBlockChecker\Issue\IssueCollection;

interface Validator
{
    public function validate(CodeNode $node, IssueCollection $issues): void;
}
