<?php

namespace SymfonyTools\CodeBlockChecker\Service\CodeRunner;

use Doctrine\RST\Nodes\CodeNode;
use SymfonyTools\CodeBlockChecker\Issue\IssueCollection;

interface Runner
{
    /**
     * @param list<CodeNode> $nodes
     */
    public function run(array $nodes, IssueCollection $issues, string $applicationDirectory): void;
}
