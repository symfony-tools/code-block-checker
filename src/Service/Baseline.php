<?php

namespace Symfony\CodeBlockChecker\Service;

use Symfony\CodeBlockChecker\Issue\Issue;
use Symfony\CodeBlockChecker\Issue\IssueCollection;

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
    public function filter(IssueCollection $issues, string $file): IssueCollection
    {
        $json = file_get_contents($file);
        try {
            $baseline = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            $baseline = $baseline['issues'];
        } catch (\JsonException $e) {
            throw new \RuntimeException('Could not parse baseline', 0, $e);
        }

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
                        $issue->getErroredLine()() === $item['code']
                    ) {
                        unset($fileBaseline[$i]);
                    } else {
                        $output->addIssue($issue);
                    }
                }
            }
        }

        return $output;
    }
}
