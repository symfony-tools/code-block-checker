<?php

namespace Symfony\CodeBlockChecker\Service;

use Doctrine\RST\Nodes\CodeNode;
use Symfony\CodeBlockChecker\Issue\Issue;
use Symfony\CodeBlockChecker\Issue\IssueCollection;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

/**
 * Runs code nodes in a real application.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class CodeNodeRunner
{
    /**
     * @param list<CodeNode> $nodes
     */
    public function runNodes(array $nodes, string $applicationDirectory): IssueCollection
    {
        $issues = new IssueCollection();
        foreach ($nodes as $node) {
            $this->processNode($node, $issues, $applicationDirectory);
        }

        return $issues;
    }

    private function processNode(CodeNode $node, IssueCollection $issues, string $applicationDirectory): void
    {
        $file = $this->getFile($node);
        if ('config/packages/' !== substr($file, 0, 16)) {
            return;
        }

        $fullPath = $applicationDirectory . '/' . $file;
        $filesystem = new Filesystem();
        $replacedOriginal = false;
        try {
            if (is_file($fullPath)) {
                $filesystem->copy($fullPath, $fullPath.'.backup');
                $replacedOriginal = true;
            }

            // Write config
            file_put_contents($fullPath, $this->getNodeContents($node));

            // Clear cache
            $filesystem->remove($applicationDirectory.'/var/cache');

            // Warmup and log errors
            $this->warmupCache($node, $issues, $applicationDirectory);
        } finally {
            // Remove added file and restore original
            $filesystem->remove($fullPath);
            if ($replacedOriginal) {
                $filesystem->copy($fullPath.'.backup', $fullPath);
                $filesystem->remove($fullPath.'.backup');
            }
        }
    }

    private function warmupCache(CodeNode $node, IssueCollection $issues, string $applicationDirectory): void
    {
        $process = new Process(['php', 'bin/console', 'cache:warmup', '--env', 'dev'], $applicationDirectory);
        $process->run();
        if ($process->isSuccessful()) {
            return;
        }

        $issues->addIssue(new Issue($node, trim($process->getErrorOutput()), 'Cache Warmup', $node->getEnvironment()->getCurrentFileName(), count(explode(PHP_EOL, $node->getValue())) - 1));
    }

    private function getFile(CodeNode $node): string
    {
        $contents = explode(PHP_EOL, $node->getValue());
        $regex = match ($node->getLanguage()) {
            'php' => '|^// ?([a-z1-9A-Z_\-/]+\.php)$|',
            'yaml' => '|^# ?([a-z1-9A-Z_\-/]+\.yaml)$|',
            //'xml' => '|^<!-- ?([a-z1-9A-Z_\-/]+\.xml) ?-->$|',
            default => null,
        };

        if (!$regex || !preg_match($regex, $contents[0], $matches)) {
            return '';
        }

        return $matches[1];
    }

    private function getNodeContents(CodeNode $node): string
    {
        $language = $node->getLanguage();
        if ('php' === $language) {
            return '<?php'.PHP_EOL.$node->getValue();
        }

        if ('xml' === $language) {
            $contents = explode(PHP_EOL, $node->getValue());
            unset($contents[0]);

            return implode(PHP_EOL, $contents);
        }

        return $node->getValue();
    }
}
