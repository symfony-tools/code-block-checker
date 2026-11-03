<?php

namespace SymfonyTools\CodeBlockChecker\Service\CodeValidator;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use SymfonyTools\CodeBlockChecker\Issue\Issue;
use SymfonyTools\CodeBlockChecker\Issue\IssueCollection;
use SymfonyTools\CodeBlockChecker\Listener\DetailedCodeNode;

class YamlValidator implements Validator
{
    public function validate(DetailedCodeNode $node, IssueCollection $issues): void
    {
        $codeNode = $node->codeNode;
        if ('yaml' !== $codeNode->getLanguage()) {
            return;
        }

        // Allow us to use "..." as a placeholder
        $contents = str_replace('...', 'null', $codeNode->getValue());
        try {
            Yaml::parse($contents, Yaml::PARSE_CONSTANT | Yaml::PARSE_CUSTOM_TAGS);
        } catch (ParseException $e) {
            if ('Duplicate key' === substr($e->getMessage(), 0, 13)) {
                return;
            }

            $issues->addIssue(new Issue($codeNode, $e->getMessage(), 'Yaml syntax', $node->fileName, $e->getParsedLine()));
        }
    }
}
