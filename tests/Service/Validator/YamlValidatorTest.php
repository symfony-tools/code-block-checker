<?php

namespace SymfonyTools\CodeBlockChecker\Tests\Service\Validator;

use phpDocumentor\Guides\Nodes\CodeNode;
use PHPUnit\Framework\TestCase;
use SymfonyTools\CodeBlockChecker\Issue\IssueCollection;
use SymfonyTools\CodeBlockChecker\Listener\DetailedCodeNode;
use SymfonyTools\CodeBlockChecker\Service\CodeValidator\Validator;
use SymfonyTools\CodeBlockChecker\Service\CodeValidator\YamlValidator;

class YamlValidatorTest extends TestCase
{
    private Validator $validator;

    protected function setUp(): void
    {
        $this->validator = new YamlValidator();
    }

    public function testInvalidYaml()
    {
        $node = new DetailedCodeNode(new CodeNode(['foobar: "test'], 'yaml'), []);
        $issues = new IssueCollection();
        $this->validator->validate($node, $issues);
        $this->assertCount(1, $issues);
        $this->assertStringContainsString('Malformed inline YAML', $issues->first());
    }
}
