<?php

namespace Symfony\CodeBlockChecker\Service;

use Doctrine\RST\Nodes\CodeNode;

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
    public static function find(CodeNode $node): int
    {
        $file = sprintf('%s/%s.rst', $node->getEnvironment()->getCurrentDirectory(), $node->getEnvironment()->getCurrentFileName());
        $contents = explode(PHP_EOL, file_get_contents($file));
        $codeBlock = explode(PHP_EOL, $node->getValue());

        foreach ($contents as $i => $line) {
            foreach ($codeBlock as $j => $needle) {
                if (!str_contains($contents[$i + $j], $needle)) {
                    continue 2;
                }
            }

            // The file's first row is 1 and our arrays first index is 0.
            return $i + 1;
        }

        return 0;
    }
}
