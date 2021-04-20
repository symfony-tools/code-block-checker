<?php

namespace Symfony\CodeBlockChecker\Tests\Service\Validator;

use Doctrine\RST\Configuration;
use Doctrine\RST\Environment;
use Doctrine\RST\Nodes\CodeNode;
use PHPUnit\Framework\TestCase;
use Symfony\CodeBlockChecker\Issue\IssueCollection;
use Symfony\CodeBlockChecker\Service\CodeValidator\PhpValidator;
use Symfony\CodeBlockChecker\Service\CodeValidator\Validator;
use Symfony\CodeBlockChecker\Service\CodeValidator\YamlValidator;

class PhpValidatorTest extends TestCase
{
    private Validator $validator;
    private Environment $environment;

    protected function setUp(): void
    {
        $this->environment = new Environment(new Configuration());
        $this->validator = new PhpValidator();
    }

    public function testValid()
    {
        $code ='
$dummy = new class {
    public $foo;
    public $bar = "notNull";
};';

        $node = new CodeNode(explode(PHP_EOL, $code));
        $node->setEnvironment($this->environment);
        $node->setLanguage('php');
        $issues = new IssueCollection();
        $this->validator->validate($node, $issues);
        $this->assertCount(0, $issues);
    }
}
