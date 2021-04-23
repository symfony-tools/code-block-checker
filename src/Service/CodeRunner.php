<?php

declare(strict_types=1);

namespace SymfonyTools\CodeBlockChecker\Service;

use Doctrine\RST\Nodes\CodeNode;
use SymfonyTools\CodeBlockChecker\Issue\IssueCollection;
use SymfonyTools\CodeBlockChecker\Service\CodeRunner\Runner;

/**
 * Run a Code Node inside a real application.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class CodeRunner
{
    /**
     * @var iterable<Runner>
     */
    private $runners;

    /**
     * @param iterable<Runner> $runners
     */
    public function __construct(iterable $runners)
    {
        $this->runners = $runners;
    }

    /**
     * @param list<CodeNode> $nodes
     */
    public function runNodes(array $nodes, string $applicationDirectory): IssueCollection
    {
        $issues = new IssueCollection();
        foreach ($this->runners as $runner) {
            $runner->run($nodes, $issues, $applicationDirectory);
        }

        return $issues;
    }
}
