<?php


namespace SymfonyTools\CodeBlockChecker\Tests\Service\CodeRunner;


use Doctrine\RST\Configuration;
use Doctrine\RST\Environment;
use Doctrine\RST\Nodes\CodeNode;
use SymfonyTools\CodeBlockChecker\Issue\IssueCollection;
use SymfonyTools\CodeBlockChecker\Service\CodeRunner\ClassExist;
use SymfonyTools\CodeBlockChecker\Service\CodeRunner\Runner;
use SymfonyTools\CodeBlockChecker\Service\CodeValidator\PhpValidator;
use SymfonyTools\CodeBlockChecker\Service\CodeValidator\Validator;

class ClassExistTest extends BaseCodeRunnerTest
{
    private Runner $runner;
    private Environment $environment;

    protected function setUp(): void
    {
        $this->environment = new Environment(new Configuration());
        $this->runner = new ClassExist();
    }
    public function testHappyPath()
    {
        $code = '
use Example\App\Foobar;
use Symfony\Component\HttpKernel;
use Foo\Bar\Baz;

echo "hello";
';
        $node = new CodeNode(explode(PHP_EOL, $code));
        $node->setEnvironment($this->environment);
        $node->setLanguage('php');
        $issues = new IssueCollection();
        $this->runner->run([$node], $issues, $this->getApplicationDirectory());
        $this->assertCount(1, $issues);

        $issue = $issues->first();
        $this->assertStringContainsString('Foo\Bar\Baz', $issue->getText());
    }


}
