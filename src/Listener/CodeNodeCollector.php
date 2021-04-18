<?php

declare(strict_types=1);

namespace Symfony\CodeBlockChecker\Listener;

use Doctrine\RST\Event\PostNodeCreateEvent;
use Doctrine\RST\Nodes\CodeNode;

/**
 * Collect all code nodes.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class CodeNodeCollector
{
    private array $nodes;

    public function getNodes(): array
    {
        return $this->nodes;
    }

    public function postNodeCreate(PostNodeCreateEvent $event)
    {
        $node = $event->getNode();
        if (!$node instanceof CodeNode) {
            return;
        }
        $this->nodes[] = $node;
    }
}
