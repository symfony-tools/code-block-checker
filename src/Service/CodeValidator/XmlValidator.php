<?php

namespace Symfony\CodeBlockChecker\Service\CodeValidator;

use Doctrine\RST\Nodes\CodeNode;
use Symfony\CodeBlockChecker\Issue\Issue;
use Symfony\CodeBlockChecker\Issue\IssueCollection;

class XmlValidator implements Validator
{
    public function validate(CodeNode $node, IssueCollection $issues): void
    {
        if ('xml' !== $node->getLanguage()) {
            return;
        }

        try {
            set_error_handler(static function ($errno, $errstr) {
                throw new \RuntimeException($errstr, $errno);
            });

            try {
                // Remove first comment only. (No multiline)
                $xml = preg_replace('#^<!-- .* -->\n#', '', $node->getValue());
                if ('' !== $xml) {
                    $xmlObject = new \SimpleXMLElement($xml);
                }
            } finally {
                restore_error_handler();
            }
        } catch (\Throwable $e) {
            if ('SimpleXMLElement::__construct(): namespace error : Namespace prefix' === substr($e->getMessage(), 0, 67)) {
                return;
            }

            $issues->addIssue(new Issue($node, $e->getMessage(), 'Invalid syntax', $node->getEnvironment()->getCurrentFileName(), 0));
        }
    }
}
