#!/usr/bin/env php

<?php

require __DIR__.'/vendor/autoload.php';

use Symfony\CodeBlockChecker\Application;
use Symfony\CodeBlockChecker\Kernel;
use Symfony\Component\Console\Input\ArgvInput;

$input = new ArgvInput();
if (null === $config = $input->getParameterOption(['--config', '-c'], null, true)) {
    $config = getcwd();
}

if (null === $env = $input->getParameterOption(['--env', '-e'], null, true)) {
    $env = 'prod';
}

putenv('APP_CONFIG_FILE='.$config);
putenv('APP_ENV='.$_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = $env);

$kernel = new Kernel($_SERVER['APP_ENV']);
$application = new Application($kernel);
$application->run($input);
