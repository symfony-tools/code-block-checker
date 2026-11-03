<?php

namespace SymfonyTools\CodeBlockChecker\Service\CodeRunner;

use phpDocumentor\Guides\Nodes\CodeNode;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use SymfonyTools\CodeBlockChecker\Issue\Issue;
use SymfonyTools\CodeBlockChecker\Issue\IssueCollection;
use SymfonyTools\CodeBlockChecker\Listener\DetailedCodeNode;

/**
 * Runs code nodes in a real application.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class ConfigurationRunner implements Runner
{
    /**
     * @param list<DetailedCodeNode> $nodes
     */
    public function run(array $nodes, IssueCollection $issues, string $applicationDirectory): void
    {
        foreach ($nodes as $node) {
            $this->processNode($node, $issues, $applicationDirectory);
        }
    }

    private function processNode(DetailedCodeNode $node, IssueCollection $issues, string $applicationDirectory): void
    {
        $codeNode = $node->codeNode;
        $explodedNode = explode("\n", $codeNode->getValue());
        $file = $this->getFile($codeNode, $explodedNode);

        if ('config/packages/' !== substr($file, 0, 16)) {
            return;
        }

        $fullPath = $applicationDirectory.'/'.$file;
        $filesystem = new Filesystem();
        $replacedOriginal = false;
        try {
            if (is_file($fullPath)) {
                $filesystem->copy($fullPath, $fullPath.'.backup');
                $replacedOriginal = true;
            }

            // Write config
            file_put_contents($fullPath, $this->getNodeContents($codeNode, $explodedNode));

            // Clear cache
            $filesystem->remove($applicationDirectory.'/var/cache');

            // Warmup and log errors
            $this->warmupCache($node, $issues, $applicationDirectory, count($explodedNode) - 1);
        } finally {
            // Remove added file and restore original
            $filesystem->remove($fullPath);
            if ($replacedOriginal) {
                $filesystem->copy($fullPath.'.backup', $fullPath);
                $filesystem->remove($fullPath.'.backup');
            }
        }
    }

    private function warmupCache(DetailedCodeNode $node, IssueCollection $issues, string $applicationDirectory, int $numberOfLines): void
    {
        $process = new Process(['php', 'bin/console', 'cache:warmup', '--env', 'dev'], $applicationDirectory);
        $process->run();
        if ($process->isSuccessful()) {
            return;
        }

        $issues->addIssue(new Issue($node->codeNode, trim($process->getErrorOutput()), 'Cache Warmup', $node->fileName, $numberOfLines));
    }

    private function getFile(CodeNode $node, array $contents): string
    {
        $regex = match ($node->getLanguage()) {
            'php' => '|^// ?([a-z1-9A-Z_\-/]+\.php)$|',
            'yaml' => '|^# ?([a-z1-9A-Z_\-/]+\.yaml)$|',
            'xml' => '|^<!-- ?([a-z1-9A-Z_\-/]+\.xml) ?-->$|',
            default => null,
        };

        if (!$regex || !preg_match($regex, $contents[0], $matches)) {
            return '';
        }

        return $matches[1];
    }

    private function getNodeContents(CodeNode $node, array $contents): string
    {
        $language = $node->getLanguage();
        if ('php' === $language) {
            return '<?php'."\n".$node->getValue();
        }

        if ('xml' === $language) {
            unset($contents[0]);
        }

        return implode("\n", $contents);
    }
}
