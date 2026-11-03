<?php

namespace SymfonyTools\CodeBlockChecker\Tests\Service\CodeRunner;

use phpDocumentor\Guides\Nodes\CodeNode;
use SymfonyTools\CodeBlockChecker\Issue\IssueCollection;
use SymfonyTools\CodeBlockChecker\Listener\DetailedCodeNode;
use SymfonyTools\CodeBlockChecker\Service\CodeRunner\ClassExist;
use SymfonyTools\CodeBlockChecker\Service\CodeRunner\Runner;

class ClassExistTest extends BaseCodeRunnerTest
{
    private Runner $runner;

    protected function setUp(): void
    {
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
        $node = new DetailedCodeNode(new CodeNode(explode(PHP_EOL, $code), 'php'), []);
        $issues = new IssueCollection();
        $this->runner->run([$node], $issues, $this->getApplicationDirectory());
        $this->assertCount(1, $issues);

        $issue = $issues->first();
        $this->assertStringContainsString('Foo\Bar\Baz', $issue->getText());
    }
}
