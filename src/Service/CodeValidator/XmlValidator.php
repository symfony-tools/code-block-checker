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

        $count = 0;
        $xml = trim(preg_replace('#^<!-- .* -->\n#', '', $node->getValue(), -1, $count));
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
            $issues->addIssue(new Issue($node, 'Foo "bar" not foo.'.$error->message, 'XML syntax', $node->getEnvironment()->getCurrentFileName(), $error->line + $count));
        }
    }
}
