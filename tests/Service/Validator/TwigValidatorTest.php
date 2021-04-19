<?php

namespace Symfony\CodeBlockChecker\Tests\Service\Validator;

use Doctrine\RST\Configuration;
use Doctrine\RST\Environment;
use Doctrine\RST\Nodes\CodeNode;
use PHPUnit\Framework\TestCase;
use Symfony\CodeBlockChecker\Issue\IssueCollection;
use Symfony\CodeBlockChecker\Service\CodeValidator\TwigValidator;
use Symfony\CodeBlockChecker\Service\CodeValidator\Validator;

class TwigValidatorTest extends TestCase
{
    private Validator $validator;
    private Environment $environment;

    protected function setUp(): void
    {
        $this->validator = new TwigValidator();
        $this->environment = new Environment(new Configuration());
    }

    public function testParseTwig()
    {
        $node = new CodeNode(['{{ form(form) }}']);
        $node->setEnvironment($this->environment);
        $node->setLanguage('twig');
        $issues = new IssueCollection();
        $this->validator->validate($node, $issues);
        $this->assertCount(0, $issues);
    }
}
