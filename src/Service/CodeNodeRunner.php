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
        if ('config/' !== substr($file, 0, 7)) {
            return;
        }

        try {
            file_put_contents($applicationDirectory . '/' . $file, $this->getNodeContents($node));
            // Clear cache
            (new Filesystem())->remove($applicationDirectory . '/var/cache');
            $this->warmupCache($node, $issues, $applicationDirectory);
        } finally {
            // Remove the file we added
            (new Filesystem())->remove($applicationDirectory . '/' . $file);
        }
    }

    private function warmupCache(CodeNode $node, IssueCollection $issues, string $applicationDirectory): void
    {
        $process = new Process(['php', 'bin/console', 'cache:warmup', '--env', 'dev'], $applicationDirectory);
        $process->run();
        if ($process->isSuccessful()) {
            return;
        }

        $error = '';
        foreach (explode(PHP_EOL, $process->getErrorOutput()) as $line) {
            $line = trim($line);
            if ('' !== $line) {
                $error.=$line.PHP_EOL;
            }
        }

        $issues->addIssue(new Issue($node, trim($error), 'Cache Warmup', $node->getEnvironment()->getCurrentFileName(), count(explode(PHP_EOL, $node->getValue()))));
    }

    private function getFile(CodeNode $node): string
    {
        $contents = explode(PHP_EOL, $node->getValue());
        $regex = match($node->getLanguage()) {
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
        if ($language === 'php') {
             return '<?php' . PHP_EOL. $node->getValue();
        }

        if ($language === 'xml') {
            $contents = explode(PHP_EOL, $node->getValue());
            unset($contents[0]);
            return implode(PHP_EOL, $contents);
        }

        return $node->getValue();
    }
}
