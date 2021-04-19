<?php

namespace Symfony\CodeBlockChecker\Service\CodeValidator;

use Doctrine\RST\Nodes\CodeNode;
use Symfony\CodeBlockChecker\Issue\Issue;
use Symfony\CodeBlockChecker\Issue\IssueCollection;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class YamlValidator implements Validator
{
    public function validate(CodeNode $node, IssueCollection $issues): void
    {
        if ('yaml' !== $node->getLanguage()) {
            return;
        }

        // Allow us to use "..." as a placeholder
        $contents = str_replace('...', 'null', $node->getValue());
        try {
            Yaml::parse($contents, Yaml::PARSE_CUSTOM_TAGS);
        } catch (ParseException $e) {
            if ('Duplicate key' === substr($e->getMessage(), 0, 13)) {
                return;
            }

            $issues->addIssue(new Issue($node, $e->getMessage(), 'Invalid syntax', $node->getEnvironment()->getCurrentFileName(), 0));
        }
    }
}
