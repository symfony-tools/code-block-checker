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

    /**
     * @dataProvider getCodeExamples
     */
    public function testCodeExamples(int $errors, string $code)
    {
        $node = new CodeNode(explode(PHP_EOL, $code));
        $node->setEnvironment($this->environment);
        $node->setLanguage('php');
        $issues = new IssueCollection();
        $this->validator->validate($node, $issues);
        $this->assertCount($errors, $issues);
    }

    public function getCodeExamples(): iterable
    {
        yield [0, '
namespace Symfony\Component\HttpKernel;

use Symfony\Component\HttpFoundation\Request;

interface HttpKernelInterface
{
    // ...

    /**
     * @return Response A Response instance
     */
    public function handle(
        Request $request,
        $type = self::MASTER_REQUEST,
        $catch = true
    );
}
'];
        yield [0, '
public static function validate($object, ExecutionContextInterface $context, $payload)
{
    // somehow you have an array of "fake names"
    $fakeNames = [/* ... */];

    // check if the name is actually a fake name
    if (in_array($object->getFirstName(), $fakeNames)) {
        $context->buildViolation("This name sounds totally fake!")
            ->atPath("firstName")
            ->addViolation()
        ;
    }
}
'];
    }
}
