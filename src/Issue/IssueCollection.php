<?php

namespace SymfonyTools\CodeBlockChecker\Issue;

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

    public function current(): mixed
    {
        return $this->issues[$this->key];
    }

    public function next(): void
    {
        ++$this->key;
    }

    public function key(): mixed
    {
        return $this->key;
    }

    public function valid(): bool
    {
        return isset($this->issues[$this->key()]);
    }

    public function rewind(): void
    {
        $this->key = 0;
    }

    public function count(): int
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

    public function append(IssueCollection $collection)
    {
        $this->issues = array_merge($this->issues, $collection->issues);
    }
}
