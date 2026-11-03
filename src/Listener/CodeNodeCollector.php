<?php

declare(strict_types=1);

namespace SymfonyTools\CodeBlockChecker\Listener;

use phpDocumentor\Guides\Compiler\CompilerContext;
use phpDocumentor\Guides\Compiler\NodeTransformer;
use phpDocumentor\Guides\Nodes\CodeNode;
use phpDocumentor\Guides\Nodes\Node;

/**
 * Collect all code nodes.
 *
 * @implements NodeTransformer<CodeNode>
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class CodeNodeCollector implements NodeTransformer
{
    private array $nodes = [];

    /** @return list<DetailedCodeNode> */
    public function getNodes(): array
    {
        return $this->nodes;
    }

    public function enterNode(Node $node, CompilerContext $compilerContext): Node
    {
        $this->nodes[] = new DetailedCodeNode($node, $compilerContext->getLoggerInformation());

        return $node;
    }

    public function leaveNode(Node $node, CompilerContext $compilerContext): ?Node
    {
        return $node;
    }

    public function supports(Node $node): bool
    {
        return $node instanceof CodeNode;
    }

    public function getPriority(): int
    {
        return 0;
    }
}
