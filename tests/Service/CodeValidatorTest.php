<?php

namespace Symfony\CodeBlockChecker\Tests\Service;

use Doctrine\RST\Configuration;
use Doctrine\RST\Environment;
use Doctrine\RST\ErrorManager;
use Doctrine\RST\Event\PostNodeCreateEvent;
use Doctrine\RST\Nodes\CodeNode;
use PHPUnit\Framework\TestCase;
use Symfony\CodeBlockChecker\Issue\IssueCollection;
use Symfony\CodeBlockChecker\Listener\CodeNodeCollector;
use Symfony\CodeBlockChecker\Service\CodeValidator;

class CodeValidatorTest extends TestCase
{
    private CodeValidator $validator;
    private Environment $environment;

    protected function setUp(): void
    {
        $this->environment = new Environment(new Configuration());
        $this->validator = new CodeValidator();
    }

    public function testInvalidYaml()
    {
        $node = new CodeNode(['foobar: "test']);
        $node->setEnvironment($this->environment);
        $node->setLanguage('yaml');
        $errors = $this->validator->validateNodes([$node]);
        $this->assertCount(1, $errors);
        $this->assertStringContainsString('Malformed inline YAML', $errors->first());
    }

    public function testParseTwig()
    {
        $node = new CodeNode(['{{ form(form) }}']);
        $node->setEnvironment($this->environment);
        $node->setLanguage('twig');
        $errors = $this->validator->validateNodes([$node]);
        $this->assertCount(0, $errors);
    }
}
