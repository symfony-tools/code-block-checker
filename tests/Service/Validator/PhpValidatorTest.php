<?php

namespace SymfonyTools\CodeBlockChecker\Tests\Service\Validator;

use phpDocumentor\Guides\Nodes\CodeNode;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SymfonyTools\CodeBlockChecker\Issue\IssueCollection;
use SymfonyTools\CodeBlockChecker\Listener\DetailedCodeNode;
use SymfonyTools\CodeBlockChecker\Service\CodeValidator\PhpValidator;
use SymfonyTools\CodeBlockChecker\Service\CodeValidator\Validator;

class PhpValidatorTest extends TestCase
{
    private Validator $validator;

    protected function setUp(): void
    {
        $this->validator = new PhpValidator();
    }

    public function testLocalLine()
    {
        // Without <?php
        $code = '$x = 2;
$y = 3;
$z = 4
echo "foo";
';

        $node = new DetailedCodeNode(new CodeNode(explode(PHP_EOL, $code), 'php'), []);
        $this->validator->validate($node, $issues = new IssueCollection());
        $this->assertCount(1, $issues);
        $this->assertEquals(4, $issues->first()->getLocalLine());

        // With <?php
        $code = '<?php
$x = 2;
$y = 3;
$z = 4
echo "foo";
';

        $node = new DetailedCodeNode(new CodeNode(explode(PHP_EOL, $code), 'php'), []);
        $this->validator->validate($node, $issues = new IssueCollection());
        $this->assertCount(1, $issues);
        $this->assertEquals(5, $issues->first()->getLocalLine());
    }

    #[DataProvider('getCodeExamples')]
    public function testCodeExamples(int $errors, string $code, ?string $language = null)
    {
        $node = new DetailedCodeNode(new CodeNode(explode(PHP_EOL, $code), $language ?? 'php'), []);
        $this->validator->validate($node, $issues = new IssueCollection());
        $this->assertCount($errors, $issues);
    }

    public static function getCodeExamples(): iterable
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
        yield [1, '
public static function validate($object, ExecutionContextInterface $context, $payload)
{
    $foo = 2a
}
'];
        yield [0, '
$this->connection = /* create your connection */;
$id = /* get the message id thanks to information or stamps present in the envelope */;
'];
        yield [1, '
$x = ;
'];
        yield [1, '
$x =
    /* a multi-line placeholder is not rewritten, to preserve line numbers */;
'];
        yield [0, '
<h1>Hello</h1>
<p><? echo $value; ?></p>
', 'html+php'];

        yield [1, '
<h1>Hello</h1>
<p><?php value foobar ?></p>
', 'html+php'];
    }
}
