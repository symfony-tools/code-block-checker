<?php

namespace SymfonyTools\CodeBlockChecker\Tests\Service\CodeRunner;

use PHPUnit\Framework\TestCase;

abstract class BaseCodeRunnerTest extends TestCase
{
    protected function getApplicationDirectory(): string
    {
        return dirname(__DIR__, 2).'/Fixtures/example-application';
    }
}
