<?php

namespace Symfony\CodeBlockChecker\Issue;

/**
 * Represent an error with some code.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class Issue implements \Stringable
{
    private string $text;
    private string $type;
    private string $file;
    private int $line;

    public function __construct(string $text, string $type, string $file, int $line)
    {
        $this->text = $text;
        $this->type = $type;
        $this->file = $file;
        $this->line = $line;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getFile(): string
    {
        return $this->file;
    }

    public function getLine(): int
    {
        return $this->line;
    }

    public function __toString()
    {
        return sprintf('[%s] %s in file %s at %d', $this->getType(), $this->getText(), $this->getFile(), $this->getLine());
    }
}
