<?php

declare(strict_types=1);

namespace SymfonyTools\CodeBlockChecker\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(name: 'cache:clear', description: 'Clear the cache directory')]
class CacheClearCommand extends Command
{
    /**
     * @var string
     */
    private $cacheDirectory;

    public function __construct(string $cacheDir)
    {
        parent::__construct();
        $this->cacheDirectory = $cacheDir;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        (new Filesystem())->remove($this->cacheDirectory);

        return 0;
    }
}
