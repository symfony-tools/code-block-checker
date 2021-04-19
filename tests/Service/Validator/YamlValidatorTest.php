<?php

namespace Symfony\CodeBlockChecker\Tests\Service\Validator;

use Doctrine\RST\Configuration;
use Doctrine\RST\Environment;
use Doctrine\RST\Nodes\CodeNode;
use PHPUnit\Framework\TestCase;
use Symfony\CodeBlockChecker\Issue\IssueCollection;
use Symfony\CodeBlockChecker\Service\CodeValidator\Validator;
use Symfony\CodeBlockChecker\Service\CodeValidator\YamlValidator;

class YamlValidatorTest extends TestCase
{
    private Validator $validator;
    private Environment $environment;

    protected function setUp(): void
    {
        $this->environment = new Environment(new Configuration());
        $this->validator = new YamlValidator();
    }

    public function testInvalidYaml()
    {
        $node = new CodeNode(['foobar: "test']);
        $node->setEnvironment($this->environment);
        $node->setLanguage('yaml');
        $issues = new IssueCollection();
        $this->validator->validate($node, $issues);
        $this->assertCount(1, $issues);
        $this->assertStringContainsString('Malformed inline YAML', $issues->first());
    }
}
