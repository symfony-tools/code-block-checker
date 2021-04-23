<?php

namespace SymfonyTools\CodeBlockChecker\Service;

use SymfonyTools\CodeBlockChecker\Issue\Issue;
use SymfonyTools\CodeBlockChecker\Issue\IssueCollection;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class Baseline
{
    public function generate(IssueCollection $issues, string $file)
    {
        $data = ['issues' => []];
        foreach ($issues as $issue) {
            $data['issues'][$issue->getFile()][$issue->getHash()] = [
                'text' => $issue->getText(),
                'type' => $issue->getType(),
                'code' => $issue->getErroredLine(),
            ];
        }

        file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
    }

    /**
     * Remove items from $issues that are in the baseline.
     */
    public function filter(IssueCollection $issues, array $baseline): IssueCollection
    {
        $baseline = $baseline['issues'];
        $output = new IssueCollection();
        $perFile = [];
        foreach ($issues as $issue) {
            if (!isset($baseline[$issue->getFile()])) {
                $output->addIssue($issue);
                continue;
            } elseif (isset($baseline[$issue->getFile()][$issue->getHash()])) {
                // This has not been modified.
                continue;
            }

            $perFile[$issue->getFile()][] = $issue;
        }

        foreach ($perFile as $file => $fileIssues) {
            $fileBaseline = $baseline[$file];
            /** @var Issue $issue */
            foreach ($fileIssues as $issue) {
                foreach ($fileBaseline as $i => $item) {
                    if (
                        $issue->getType() === $item['type'] &&
                        $issue->getText() === $item['text'] &&
                        $issue->getErroredLine() === $item['code']
                    ) {
                        unset($fileBaseline[$i]);
                        continue 2;
                    }
                }

                $output->addIssue($issue);
            }
        }

        return $output;
    }
}
