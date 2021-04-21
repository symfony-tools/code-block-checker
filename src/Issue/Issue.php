<?php

namespace Symfony\CodeBlockChecker\Issue;

use Doctrine\RST\Nodes\CodeNode;
use Symfony\CodeBlockChecker\Service\LineDetector;

/**
 * Represent an error with some code.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class Issue implements \Stringable
{
    private CodeNode $node;
    private string $text;
    private string $type;
    private string $file;
    private ?string $erroredLine;

    /**
     * The line in the file.
     */
    private ?int $line;

    /**
     * The local line is inside the code node.
     */
    private int $localLine;

    public function __construct(CodeNode $node, string $text, string $type, string $file, int $localLine)
    {
        $this->node = $node;
        $this->text = trim($text);
        $this->type = $type;
        $this->file = $file;
        $this->localLine = $localLine;
        $this->line = null;
        $this->erroredLine = null;
    }

    public function getHash(): string
    {
        return sha1($this->node->getValue());
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
        return $this->file.'.rst';
    }

    public function getLine(): int
    {
        if (null === $this->line) {
            $offset = LineDetector::find($this->node);
            $this->line = $offset + $this->localLine;
        }

        return $this->line;
    }

    public function getLocalLine(): int
    {
        return $this->localLine;
    }

    public function getErroredLine()
    {
        if (null === $this->erroredLine) {
            $lines = explode(PHP_EOL, $this->node->getValue());
            // We do -1 because the $lines array is zero-index and the error message is 1-index
            $this->erroredLine = $lines[max(0, $this->localLine - 1)];
        }

        return $this->erroredLine;
    }

    public function __toString()
    {
        return sprintf('[%s] %s in file %s at %d', $this->getType(), $this->getText(), $this->getFile(), $this->getLine());
    }
}
