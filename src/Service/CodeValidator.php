<?php

declare(strict_types=1);

namespace Symfony\CodeBlockChecker\Service;

use Symfony\CodeBlockChecker\Issue\IssueCollection;
use Symfony\CodeBlockChecker\Service\CodeValidator\Validator;

/**
 * Verify that all code nodes has the correct syntax.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class CodeValidator
{
    /**
     * @var iterable<Validator>
     */
    private $validators;

    /**
     * @param iterable<Validator> $validators
     */
    public function __construct(iterable $validators)
    {
        $this->validators = $validators;
    }

    public function validateNodes(array $nodes): IssueCollection
    {
        $issues = new IssueCollection();
        foreach ($nodes as $node) {
            foreach ($this->validators as $validator) {
                $validator->validate($node, $issues);
            }
        }

        return $issues;
    }
}
