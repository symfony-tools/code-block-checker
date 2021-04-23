<?php

namespace Symfony\CodeBlockChecker\Service\CodeRunner;

use Doctrine\RST\Nodes\CodeNode;
use Symfony\CodeBlockChecker\Issue\IssueCollection;

interface Runner
{
    /**
     * @param list<CodeNode> $nodes
     */
    public function run(array $nodes, IssueCollection $issues, string $applicationDirectory): void;
}
