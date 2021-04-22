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
        $environment = $node->getEnvironment();
        if ('' === $environment->getCurrentFileName()) {
            return 0;
        }

        $file = sprintf('%s/%s.rst', $environment->getCurrentDirectory(), $environment->getCurrentFileName());
        $contents = explode("\n", file_get_contents($file));
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
