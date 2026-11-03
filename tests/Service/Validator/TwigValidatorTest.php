<?php

namespace SymfonyTools\CodeBlockChecker\Tests\Service\Validator;

use phpDocumentor\Guides\Nodes\CodeNode;
use PHPUnit\Framework\TestCase;
use SymfonyTools\CodeBlockChecker\Issue\IssueCollection;
use SymfonyTools\CodeBlockChecker\Listener\DetailedCodeNode;
use SymfonyTools\CodeBlockChecker\Service\CodeValidator\TwigValidator;
use SymfonyTools\CodeBlockChecker\Service\CodeValidator\Validator;

class TwigValidatorTest extends TestCase
{
    private Validator $validator;

    protected function setUp(): void
    {
        $this->validator = new TwigValidator();
    }

    public function testParseTwig()
    {
        $node = new DetailedCodeNode(new CodeNode(['{{ form(form) }}'], 'twig'), []);
        $issues = new IssueCollection();
        $this->validator->validate($node, $issues);
        $this->assertCount(0, $issues);
    }

    public function testParseSecurityFunctions()
    {
        $node = new DetailedCodeNode(new CodeNode([
            '{{ is_granted("ROLE_ADMIN") }}',
            '{{ is_granted_for_user(user, "ROLE_ADMIN") }}',
            '{{ access_decision("ROLE_ADMIN").isGranted }}',
            '{{ access_decision_for_user(user, "ROLE_ADMIN").isGranted }}',
            '{{ impersonation_path("_exit") }}',
            '{{ impersonation_url("_exit") }}',
            '{{ impersonation_exit_path() }}',
            '{{ impersonation_exit_url() }}',
        ], 'twig'), []);
        $issues = new IssueCollection();
        $this->validator->validate($node, $issues);
        $this->assertCount(0, $issues);
    }
}
