<?php

namespace SymfonyTools\CodeBlockChecker\Listener;

use phpDocumentor\Guides\Nodes\CodeNode;

final class DetailedCodeNode
{
    public string $fileName {
        get => $this->loggerInfo['rst-file'] ?? '';
    }

    /** @param array<string, string> $loggerInfo */
    public function __construct(
        public readonly CodeNode $codeNode,
        private readonly array $loggerInfo,
    ) {
    }
}
