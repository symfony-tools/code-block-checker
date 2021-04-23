<?php

declare(strict_types=1);

namespace SymfonyTools\CodeBlockChecker\Command;

use Doctrine\RST\Builder\Documents;
use Doctrine\RST\Builder\ParseQueue;
use Doctrine\RST\Builder\ParseQueueProcessor;
use Doctrine\RST\ErrorManager;
use Doctrine\RST\Event\PostNodeCreateEvent;
use Doctrine\RST\Meta\Metas;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use SymfonyDocsBuilder\BuildConfig;
use SymfonyTools\CodeBlockChecker\Issue\IssueCollection;
use SymfonyTools\CodeBlockChecker\Listener\CodeNodeCollector;
use SymfonyTools\CodeBlockChecker\Service\Baseline;
use SymfonyTools\CodeBlockChecker\Service\CodeNodeRunner;
use SymfonyTools\CodeBlockChecker\Service\CodeValidator;

class CheckDocsCommand extends Command
{
    protected static $defaultName = 'verify:docs';

    private SymfonyStyle $io;
    private ParseQueueProcessor $queueProcessor;
    private CodeNodeCollector $collector;
    private CodeValidator $validator;
    private Baseline $baseline;
    private CodeRunner $codeRunner;

    public function __construct(CodeValidator $validator, Baseline $baseline, CodeRunner $codeRunner)
    {
        parent::__construct(self::$defaultName);
        $this->validator = $validator;
        $this->baseline = $baseline;
        $this->codeRunner = $codeRunner;
    }

    protected function configure()
    {
        $this
            ->addArgument('source-dir', InputArgument::REQUIRED, 'RST files Source directory')
            ->addArgument('files', InputArgument::IS_ARRAY, 'RST files that should be verified.', [])
            ->addOption('output-format', null, InputOption::VALUE_REQUIRED, 'Valid options are "github" and "console"', 'console')
            ->addOption('symfony-application', null, InputOption::VALUE_REQUIRED, 'Path to a symfony application to test the code blocks', false)
            ->addOption('generate-baseline', null, InputOption::VALUE_REQUIRED, 'Generate a new baseline', false)
            ->addOption('baseline', null, InputOption::VALUE_REQUIRED, 'Use a baseline', false)
            ->setDescription('Make sure the docs blocks are valid')
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);

        $sourceDir = $input->getArgument('source-dir');
        if (!file_exists($sourceDir)) {
            throw new \InvalidArgumentException(sprintf('RST source directory "%s" does not exist', $sourceDir));
        }
        $buildConfig = new BuildConfig();
        $buildConfig->setContentDir($sourceDir);

        $kernel = \SymfonyDocsBuilder\KernelFactory::createKernel($buildConfig);
        $configuration = $kernel->getConfiguration();
        $configuration->silentOnError(true);
        $eventManager = $configuration->getEventManager();
        $eventManager->addEventListener(PostNodeCreateEvent::POST_NODE_CREATE, $this->collector = new CodeNodeCollector());

        $metas = new Metas();
        $documents = new Documents(new Filesystem(), $metas);

        $this->queueProcessor = new ParseQueueProcessor($kernel, new ErrorManager($configuration), $metas, $documents, $sourceDir, '/foo/target', 'rst');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // This will collect all CodeNodes
        $this->queueProcessor->process($this->prepareParseQueue($input));

        // Verify code blocks
        $issues = $this->validator->validateNodes($this->collector->getNodes());
        if ($applicationDir = $input->getOption('symfony-application')) {
            $issues->append($this->codeRunner->runNodes($this->collector->getNodes(), $applicationDir));
        }

        if ($baselineFile = $input->getOption('generate-baseline')) {
            $this->baseline->generate($issues, $baselineFile);

            return Command::SUCCESS;
        }

        if ($baselineFile = $input->getOption('baseline')) {
            $json = file_get_contents($baselineFile);
            try {
                $baseline = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                throw new \RuntimeException('Could not parse baseline', 0, $e);
            }
            $issues = $this->baseline->filter($issues, $baseline);
        }

        if (count($issues) > 0) {
            $this->outputIssue($input->getOption('output-format'), $issues);

            return Command::FAILURE;
        }

        $this->io->success('Build completed successfully!');

        return Command::SUCCESS;
    }

    private function findFiles(string $directory): array
    {
        $files = [];
        $finder = new Finder();
        $finder->in($directory)
            ->name('*.rst');
        foreach ($finder as $file) {
            $files[] = $file->getRelativePathname();
        }

        return $files;
    }

    private function prepareParseQueue(InputInterface $input): ParseQueue
    {
        $sourceDirectory = $input->getArgument('source-dir');
        $files = $input->getArgument('files');
        if ([] === $files) {
            $files = $this->findFiles($sourceDirectory);
        } else {
            foreach ($files as $i => $file) {
                if (!file_exists($sourceDirectory.DIRECTORY_SEPARATOR.$file)) {
                    unset($files[$i]);
                    $this->outputWarning($input->getOption('output-format'), sprintf('Could not find file "%s"', $file));
                }
            }
        }

        $parseQueue = new ParseQueue();
        foreach ($files as $filename) {
            // Only parse .rst files
            if ('.rst' === substr($filename, -4)) {
                // Remove ".rst"
                $filename = substr($filename, 0, -4);
                $parseQueue->addFile(ltrim($filename, '/'), true);
            }
        }

        return $parseQueue;
    }

    private function outputWarning(string $format, string $text): void
    {
        if ('console' === $format) {
            $this->io->warning($text);
        } elseif ('github' === $format) {
            $this->io->writeln('::warning::'.$text);
        }
    }

    private function outputIssue(string $format, IssueCollection $issues): void
    {
        if ('console' === $format) {
            foreach ($issues as $issue) {
                $this->io->writeln($issue->__toString());
            }

            $this->io->error(sprintf('Build completed with %s errors', $issues->count()));
        } elseif ('github' === $format) {
            foreach ($issues as $issue) {
                // We use urlencoded '\n'
                $text = str_replace(PHP_EOL, '%0A', $issue->getText());
                $this->io->writeln(sprintf('::error file=%s,line=%s::[%s] %s', $issue->getFile(), $issue->getLine(), $issue->getType(), $text));
            }
        }
    }
}
