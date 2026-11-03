<?php

namespace SymfonyTools\CodeBlockChecker\Service\CodeRunner;

use SymfonyTools\CodeBlockChecker\Issue\IssueCollection;
use SymfonyTools\CodeBlockChecker\Listener\DetailedCodeNode;

interface Runner
{
    /**
     * @param list<DetailedCodeNode> $nodes
     */
    public function run(array $nodes, IssueCollection $issues, string $applicationDirectory): void;
}
