<?php

namespace Symfony\CodeBlockChecker\Issue;

class IssueCollection implements \Iterator, \Countable
{
    /**
     * @var list<Issue>
     */
    private array $issues = [];
    private int $key = 0;

    public function addIssue(Issue $issue)
    {
        $this->issues[] = $issue;
    }

    public function current()
    {
        return $this->issues[$this->key];
    }

    public function next()
    {
        ++$this->key;
    }

    public function key()
    {
        return $this->key;
    }

    public function valid()
    {
        return isset($this->issues[$this->key()]);
    }

    public function rewind()
    {
        $this->key = 0;
    }

    public function count()
    {
        return count($this->issues);
    }

    /**
     * Get first issue or null.
     */
    public function first(): ?Issue
    {
        return $this->issues[0] ?? null;
    }
}
