<?php


namespace Symfony\CodeBlockChecker\Issue;

use Doctrine\RST\ErrorManager;

class IssueManger extends ErrorManager
{
    /**
     * @var list<Issue>
     */
    private array $issues = [];

    public function addIssue(Issue $issue)
    {
        $this->issues[] = $issue;
        parent::error($issue->__toString());
    }

    /**
     * @return list<Issue>
     */
    public function getIssues(): array
    {
        return $this->issues;
    }
}
