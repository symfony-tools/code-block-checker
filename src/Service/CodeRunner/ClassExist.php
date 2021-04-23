<?php

namespace SymfonyTools\CodeBlockChecker\Service\CodeRunner;

use Doctrine\RST\Nodes\CodeNode;
use Symfony\Component\Process\Process;
use SymfonyTools\CodeBlockChecker\Issue\Issue;
use SymfonyTools\CodeBlockChecker\Issue\IssueCollection;

/**
 * Verify that any reference to a PHP class is actually a real class.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class ClassExist implements Runner
{
    /**
     * @param list<CodeNode> $nodes
     */
    public function run(array $nodes, IssueCollection $issues, string $applicationDirectory): void
    {
        $classes = [];
        foreach ($nodes as $node) {
            $classes = array_merge($classes, $this->getClasses($node));
        }

        $this->testClasses($classes, $issues, $applicationDirectory);
    }

    private function getClasses(CodeNode $node): array
    {
        $language = $node->getLanguage() ?? 'php';
        if (!in_array($language, ['php', 'php-symfony', 'php-standalone', 'php-annotations'])) {
            return [];
        }

        $classes = [];
        foreach (explode("\n", $node->getValue()) as $i => $line) {
            $matches = [];
            if (0 !== strpos($line, 'use ') || !preg_match('|^use (.*\\\.*); *?$|m', $line, $matches)) {
                continue;
            }

            $class = $matches[1];
            if (false !== $pos = strpos($class, ' as ')) {
                $class = substr($class, 0, $pos);
            }

            if (false !== $pos = strpos($class, 'function ')) {
                continue;
            }

            $explode = explode('\\', $class);
            if (
                'App' === $explode[0] || 'Acme' === $explode[0]
                || (3 === count($explode) && 'Symfony' === $explode[0] && ('Component' === $explode[1] || 'Config' === $explode[1]))
            ) {
                continue;
            }

            $classes[] = ['class' => $class, 'line' => $i + 1, 'node' => $node];
        }

        return $classes;
    }

    /**
     * Make sure PHP classes exists in the application directory.
     *
     * @param array{int, array{ class: string, line: int, node: CodeNode } } $classes
     */
    private function testClasses(array $classes, IssueCollection $issues, string $applicationDirectory): void
    {
        $fileBody = '';
        foreach ($classes as $i => $data) {
            $fileBody .= sprintf('%s => isLoaded("%s"),', $i, $data['class'])."\n";
        }

        file_put_contents($applicationDirectory.'/class_exist.php', strtr('<?php
require __DIR__.\'/vendor/autoload.php\';

function isLoaded($class) {
    return class_exists($class) || interface_exists($class) || trait_exists($class);
}

echo json_encode([ARRAY_CONTENT]);

', ['ARRAY_CONTENT' => $fileBody]));

        $process = new Process(['php', 'class_exist.php'], $applicationDirectory);
        $process->run();

        if (!$process->isSuccessful()) {
            // TODO handle this
            return;
        }

        $output = $process->getOutput();
        try {
            $results = json_decode($output, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            // TODO handle this
            return;
        }

        foreach ($classes as $i => $data) {
            if (!$results[$i]) {
                $text = sprintf('Class, interface or trait with name "%s" does not exist', $data['class']);
                $issues->addIssue(new Issue($data['node'], $text, 'Missing class', $data['node']->getEnvironment()->getCurrentFileName(), $data['line']));
            }
        }
    }
}
