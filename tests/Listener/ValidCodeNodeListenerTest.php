<?php

namespace Symfony\CodeBlockChecker\Tests\Listener;

use Doctrine\RST\Configuration;
use Doctrine\RST\Environment;
use Doctrine\RST\ErrorManager;
use Doctrine\RST\Event\PostNodeCreateEvent;
use Doctrine\RST\Nodes\CodeNode;
use PHPUnit\Framework\TestCase;
use Symfony\CodeBlockChecker\Listener\ValidCodeNodeListener;

class ValidCodeNodeListenerTest extends TestCase
{
    private ErrorManager $errorManager;
    private ValidCodeNodeListener $listener;
    private Environment $environment;

    protected function setUp(): void
    {
        $config = new Configuration();
        $config->silentOnError();
        $config->abortOnError(false);

        $this->errorManager = new ErrorManager($config);
        $this->listener = new ValidCodeNodeListener($this->errorManager);
        $this->environment = new Environment($config);
    }

    public function testInvalidYaml()
    {
        $node = new CodeNode(['foobar: "test']);
        $node->setEnvironment($this->environment);
        $node->setLanguage('yaml');
        $this->listener->postNodeCreate(new PostNodeCreateEvent($node));

        $errors = $this->errorManager->getErrors();
        $this->assertCount(1, $errors);

        $this->assertStringContainsString('Invalid Yaml', $errors[0]);
    }

    public function testParseTwig()
    {
        $node = new CodeNode(['{{ form(form) }}']);
        $node->setEnvironment($this->environment);
        $node->setLanguage('twig');
        $this->listener->postNodeCreate(new PostNodeCreateEvent($node));

        $errors = $this->errorManager->getErrors();
        $this->assertCount(0, $errors);
    }
}
