<?php

namespace SymfonyCodeBlockChecker;


use Psr\Container\ContainerInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\DependencyInjection\AddConsoleCommandPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\DependencyInjection\Loader\DirectoryLoader;
use Symfony\Component\DependencyInjection\Loader\FileLoader;
use Symfony\Component\DependencyInjection\Loader\GlobFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class Kernel
{
    private $booted = false;
    private $debug;
    private $env;

    /** @var Container */
    private $container;

    public function __construct(string $env)
    {
        $this->debug = 'prod' !== $env;
        $this->env = $env;
    }

    public function boot()
    {
        if ($this->booted) {
            return;
        }

        $cacheDir = $this->getCacheDir();
        if (!is_dir($cacheDir)) {
            if (false === @mkdir($cacheDir, 0777, true) && !is_dir($cacheDir)) {
                throw new \RuntimeException(sprintf("Unable to create the cache directory (%s)\n", $cacheDir));
            }
        } elseif (!is_writable($cacheDir)) {
            throw new \RuntimeException(sprintf("Unable to write in the cache directory (%s)\n", $cacheDir));
        }

        $containerDumpFile = $cacheDir.'/container.php';
        if ($this->debug || !file_exists($containerDumpFile)) {
            $container = new ContainerBuilder();
            $container->setParameter('kernel.project_dir', $this->getProjectDir());
            $container->setParameter('kernel.cache_dir', $cacheDir);
            $container->setParameter('kernel.environment', $this->env);
            $container->setParameter('kernel.debug', $this->debug);

            $container->registerForAutoconfiguration(Command::class)->addTag('console.command');

            $loader = $this->getContainerLoader($container, $this->getProjectDir().'/config');
            $loader->load('{packages}/*.yaml', 'glob');
            $loader->load('{packages}/'.$this->env.'/*.yaml', 'glob');
            $loader->load('services.yaml');
            $loader->load('{services}_'.$this->env.'.yaml', 'glob');

            $container->addCompilerPass(new AddConsoleCommandPass(), PassConfig::TYPE_BEFORE_REMOVING);

            $container->compile();

            //dump the container
            file_put_contents(
                $containerDumpFile,
                (new PhpDumper($container))->dump(['class' => 'CachedContainer'])
            );
        }

        require_once $containerDumpFile;
        $this->container = new \CachedContainer();
        $this->booted = true;
    }

    public function getContainer(): ?ContainerInterface
    {
        return $this->container;
    }

    private function getContainerLoader(ContainerBuilder $container, string $configDir)
    {
        $locator = new FileLocator($configDir);
        $resolver = new LoaderResolver([
            new YamlFileLoader($container, $locator),
            new GlobFileLoader($container, $locator),
            new DirectoryLoader($container, $locator),
        ]);

        return new DelegatingLoader($resolver);
    }

    private function getProjectDir()
    {
        return dirname(__DIR__);
    }

    public function getCacheDir()
    {
        if ($this->debug) {
            return $this->getProjectDir().'/var/cache/'.$this->env;
        }

        return sys_get_temp_dir().'/symfony-code-block-checker/cache/'.$this->env;
    }

    public function getEnvironment(): string
    {
        return $this->env;
    }
}
