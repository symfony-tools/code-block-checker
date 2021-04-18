<?php

declare(strict_types=1);

namespace Symfony\CodeBlockChecker\Listener;

use Doctrine\RST\Event\PostNodeCreateEvent;
use Doctrine\RST\Nodes\CodeNode;
use Symfony\CodeBlockChecker\Issue\Issue;
use Symfony\CodeBlockChecker\Issue\IssueManger;
use Symfony\CodeBlockChecker\Twig\DummyExtension;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use Twig\Environment;
use Twig\Error\SyntaxError;
use Twig\Loader\ArrayLoader;
use Twig\Source;

/**
 * Verify that all code nodes has the correct syntax.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class ValidCodeNodeListener
{
    private $errorManager;
    private $twig;

    public function __construct(IssueManger $errorManager)
    {
        $this->errorManager = $errorManager;
    }

    public function postNodeCreate(PostNodeCreateEvent $event)
    {
        $node = $event->getNode();
        if (!$node instanceof CodeNode) {
            return;
        }

        $language = $node->getLanguage() ?? ($node->isRaw() ? null : 'php');
        if (in_array($language, ['php', 'php-symfony', 'php-standalone', 'php-annotations'])) {
            $this->validatePhp($node);
        } elseif ('yaml' === $language) {
            $this->validateYaml($node);
        } elseif ('xml' === $language) {
            $this->validateXml($node);
        } elseif ('json' === $language) {
            $this->validateJson($node);
        } elseif (in_array($language, ['twig', 'html+twig'])) {
            $this->validateTwig($node);
        }
    }

    private function validatePhp(CodeNode $node)
    {
        $file = sys_get_temp_dir().'/'.uniqid('doc_builder', true).'.php';
        $contents = $node->getValue();
        if (!preg_match('#class [a-zA-Z]+#s', $contents) && preg_match('#(public|protected|private) (\$[a-z]+|function)#s', $contents)) {
            $contents = 'class Foobar {'.$contents.'}';
        }

        // Allow us to use "..." as a placeholder
        $contents = str_replace('...', 'null', $contents);
        file_put_contents($file, '<?php'.PHP_EOL.$contents);

        $process = new Process(['php', '-l', $file]);
        $process->run();
        if ($process->isSuccessful()) {
            return;
        }

        $line = 0;
        $text = str_replace($file, 'example.php', $process->getErrorOutput());
        if (preg_match('| in example.php on line ([0-9]+)|s', $text, $matches)) {
            $text = str_replace($matches[0], '', $text);
            $line = (int) $matches[1];
        }
        $this->errorManager->addIssue(new Issue($text, 'Invalid syntax', $node->getEnvironment()->getCurrentFileName(), $line));
    }

    private function validateXml(CodeNode $node)
    {
        try {
            set_error_handler(static function ($errno, $errstr) {
                throw new \RuntimeException($errstr, $errno);
            });

            try {
                // Remove first comment only. (No multiline)
                $xml = preg_replace('#^<!-- .* -->\n#', '', $node->getValue());
                if ('' !== $xml) {
                    $xmlObject = new \SimpleXMLElement($xml);
                }
            } finally {
                restore_error_handler();
            }
        } catch (\Throwable $e) {
            if ('SimpleXMLElement::__construct(): namespace error : Namespace prefix' === substr($e->getMessage(), 0, 67)) {
                return;
            }

            $this->errorManager->addIssue(new Issue($e->getMessage(), 'Invalid syntax', $node->getEnvironment()->getCurrentFileName(), 0));
        }
    }

    private function validateYaml(CodeNode $node)
    {
        // Allow us to use "..." as a placeholder
        $contents = str_replace('...', 'null', $node->getValue());
        try {
            Yaml::parse($contents, Yaml::PARSE_CUSTOM_TAGS);
        } catch (ParseException $e) {
            if ('Duplicate key' === substr($e->getMessage(), 0, 13)) {
                return;
            }

            $this->errorManager->addIssue(new Issue($e->getMessage(), 'Invalid syntax', $node->getEnvironment()->getCurrentFileName(), 0));
        }
    }

    private function validateTwig(CodeNode $node)
    {
        if (null === $this->twig) {
            $this->twig = new Environment(new ArrayLoader());
            $this->twig->addExtension(new DummyExtension());
        }

        try {
            $tokens = $this->twig->tokenize(new Source($node->getValue(), $node->getEnvironment()->getCurrentFileName()));
            // We cannot parse the TokenStream because we dont have all extensions loaded.
            $this->twig->parse($tokens);
        } catch (SyntaxError $e) {
            $this->errorManager->addIssue(new Issue($e->getMessage(), 'Invalid syntax', $node->getEnvironment()->getCurrentFileName(), 0));
        }
    }

    private function validateJson(CodeNode $node)
    {
        try {
            $data = json_decode($node->getValue(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $this->errorManager->addIssue(new Issue($e->getMessage(), 'Invalid syntax', $node->getEnvironment()->getCurrentFileName(), 0));
        }
    }
}
