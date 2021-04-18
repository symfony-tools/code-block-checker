<?php

declare(strict_types=1);

namespace Symfony\CodeBlockChecker\Command;

use Doctrine\RST\Builder\Documents;
use Doctrine\RST\Builder\ParseQueue;
use Doctrine\RST\Builder\ParseQueueProcessor;
use Doctrine\RST\ErrorManager;
use Doctrine\RST\Event\PostNodeCreateEvent;
use Doctrine\RST\Meta\Metas;
use Symfony\CodeBlockChecker\Issue\IssueCollection;
use Symfony\CodeBlockChecker\Listener\CodeNodeCollector;
use Symfony\CodeBlockChecker\Service\CodeValidator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use SymfonyDocsBuilder\BuildConfig;

class CheckDocsCommand extends Command
{
    protected static $defaultName = 'verify:docs';

    private SymfonyStyle $io;
    private IssueCollection $errorManager;
    private ParseQueueProcessor $queueProcessor;
    private CodeNodeCollector $collector;
    private CodeValidator $validator;

    public function __construct(CodeValidator $validator)
    {
        parent::__construct(self::$defaultName);
        $this->validator = $validator;
    }

    protected function configure()
    {
        $this
            ->addArgument('source-dir', InputArgument::REQUIRED, 'RST files Source directory')
            ->addArgument('files', InputArgument::IS_ARRAY + InputArgument::REQUIRED, 'RST files that should be verified.')
            ->addOption('output-format', null, InputOption::VALUE_OPTIONAL, 'Valid options are github and console', 'github')
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
        $files = $input->getArgument('files');
        $parseQueue = new ParseQueue();
        foreach ($files as $filename) {
            // Remove ".rst"
            if ('.rst' === substr($filename, -4)) {
                $filename = substr($filename, 0, -4);
            }
            $parseQueue->addFile(ltrim($filename, '/'), true);
        }

        // This will collect all CodeNodes
        $this->queueProcessor->process($parseQueue);
        $issues = $this->validator->validateNodes($this->collector->getNodes());

        $issueCount = count($issues);
        if ($issueCount > 0) {
            $format = $input->getOption('output-format');
            if ('console' === $format) {
                foreach ($issues as $issue) {
                    $this->io->writeln($issue->__toString());
                }
            } elseif ('github' === $format) {
                foreach ($issues as $issue) {
                    $this->io->writeln(sprintf('::error file=%s,line=%s::[%s] %s', $issue->getFile(), $issue->getLine(), $issue->getType(), $issue->getText()));
                }
            }

            $this->io->error(sprintf('Build completed with %s errors', $issueCount));

            return Command::FAILURE;
        }
        $this->io->success('Build completed successfully!');

        return Command::SUCCESS;
    }
}
