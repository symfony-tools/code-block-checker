<?php

namespace SymfonyTools\CodeBlockChecker\Service;

use phpDocumentor\Guides\Nodes\CodeNode;

/**
 * Find the line a CodeNode belongs to.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class LineDetector
{
    /**
     * Get the line number where the $node starts.
     */
    public static function find(CodeNode $node, string $fileName): int
    {
        if ('' === $fileName) {
            return 0;
        }

        $contents = explode("\n", file_get_contents($fileName));
        $codeBlock = explode("\n", $node->getValue());

        foreach ($contents as $i => $line) {
            foreach ($codeBlock as $j => $needle) {
                if (!str_contains($contents[$i + $j], $needle)) {
                    continue 2;
                }
            }

            return $i;
        }

        return 0;
    }
}
