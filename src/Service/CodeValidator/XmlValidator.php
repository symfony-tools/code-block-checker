<?php

namespace SymfonyTools\CodeBlockChecker\Service\CodeValidator;

use SymfonyTools\CodeBlockChecker\Issue\Issue;
use SymfonyTools\CodeBlockChecker\Issue\IssueCollection;
use SymfonyTools\CodeBlockChecker\Listener\DetailedCodeNode;

class XmlValidator implements Validator
{
    public function validate(DetailedCodeNode $node, IssueCollection $issues): void
    {
        $codeNode = $node->codeNode;
        if ('xml' !== $codeNode->getLanguage()) {
            return;
        }

        $count = 0;
        $xml = trim(preg_replace('#^<!-- .* -->\n#', '', $codeNode->getValue(), -1, $count));
        if ('' === $xml) {
            return;
        }

        libxml_use_internal_errors(true);
        $doc = new \DOMDocument();
        $doc->loadXML($xml);

        /** @var \LibXMLError[] $errors */
        $errors = libxml_get_errors();
        libxml_clear_errors();

        if (!$errors) {
            return;
        }

        $error = null;
        foreach ($errors as $candidate) {
            if (preg_match('#^Namespace prefix .+ is not defined$#', $candidate->message)) {
                // Ignore take namespace error
                continue;
            }

            if (0 === strpos($candidate->message, 'Extra content at the end of the document')) {
                // This is because there is not element wrapping the content
                continue;
            }

            $error = $candidate;
            break;
        }

        if (null !== $error) {
            $issues->addIssue(new Issue($codeNode, $error->message, 'XML syntax', $node->fileName, $error->line + $count));
        }
    }
}
