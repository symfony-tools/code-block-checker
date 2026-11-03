<?php

declare(strict_types=1);

namespace SymfonyTools\CodeBlockChecker\Command;

use League\Flysystem\Filesystem as LeagueFilesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Tactician\CommandBus;
use phpDocumentor\FileSystem\FlysystemV3\FlysystemV3;
use phpDocumentor\Guides\Compiler\CompilerContext;
use phpDocumentor\Guides\Handlers\CompileDocumentsCommand;
use phpDocumentor\Guides\Handlers\ParseDirectoryCommand;
use phpDocumentor\Guides\Handlers\ParseFileCommand;
use phpDocumentor\Guides\Nodes\ProjectNode;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use SymfonyTools\CodeBlockChecker\Issue\IssueCollection;
use SymfonyTools\CodeBlockChecker\Listener\CodeNodeCollector;
use SymfonyTools\CodeBlockChecker\Service\Baseline;
use SymfonyTools\CodeBlockChecker\Service\CodeRunner;
use SymfonyTools\CodeBlockChecker\Service\CodeValidator;
use SymfonyTools\DocsBuilder\GuidesExtension\DocsKernel;

#[AsCommand(name: 'verify:docs')]
class CheckDocsCommand extends Command
{
    private SymfonyStyle $io;
    private CodeNodeCollector $collector;
    private CodeValidator $validator;
    private Baseline $baseline;
    private CodeRunner $codeRunner;
    private DocsKernel $docsKernel;

    public function __construct(CodeValidator $validator, Baseline $baseline, CodeRunner $codeRunner)
    {
        parent::__construct();
        $this->validator = $validator;
        $this->baseline = $baseline;
        $this->codeRunner = $codeRunner;
    }

    protected function configure(): void
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

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);

        $sourceDir = $input->getArgument('source-dir');
        if (!file_exists($sourceDir)) {
            throw new \InvalidArgumentException(sprintf('RST source directory "%s" does not exist', $sourceDir));
        }

        $this->docsKernel = DocsKernel::create([
            new class implements ExtensionInterface, CompilerPassInterface {
                public function load(array $configs, ContainerBuilder $container): void
                {
                    $container->register(CodeNodeCollector::class)
                        ->setPublic(true)
                        ->addTag('phpdoc.guides.compiler.nodeTransformers');
                }

                public function process(ContainerBuilder $container): void
                {
                    $container->getDefinition(CommandBus::class)->setPublic(true);
                }

                public function getAlias(): string
                {
                    return 'code-block-checker';
                }
            },
        ]);
        $this->collector = $this->docsKernel->get(CodeNodeCollector::class);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // This will collect all CodeNodes
        $sourceDir = new FlysystemV3(new LeagueFilesystem(new LocalFilesystemAdapter($input->getArgument('source-dir'))));
        $files = $input->getArgument('files');
        $command = null;
        $projectNode = new ProjectNode('Symfony', '0.1');
        $commandBus = $this->docsKernel->get(CommandBus::class);
        if ([] === $files) {
            $documents = $commandBus->handle(
                new ParseDirectoryCommand($sourceDir, '/', 'rst', $projectNode)
            );
        } else {
            $documents = [];
            foreach ($files as $file) {
                if (!$sourceDir->has($file)) {
                    $this->outputNotice($input->getOption('output-format'), sprintf('Skipping "%s" (file does not exist)', $file));

                    continue;
                }

                $documents[] = $commandBus->handle(
                    new ParseFileCommand($sourceDir, '/', substr($file, 0, -4), 'rst', 1, $projectNode, true)
                );
            }
        }

        $commandBus->handle(
            new CompileDocumentsCommand($documents, new CompilerContext($projectNode))
        );

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

    private function outputNotice(string $format, string $text): void
    {
        if ('console' === $format) {
            $this->io->note($text);
        } elseif ('github' === $format) {
            $this->io->writeln('::notice::'.$text);
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
                $this->io->writeln(sprintf('::error file=%s,line=%s::[%s] %s (in %s on line %d)', $issue->getFile(), $issue->getLine(), $issue->getType(), $text, $issue->getFile(), $issue->getLine()));
            }
        }
    }
}
