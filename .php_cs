<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__.'/src')
    ->in(__DIR__.'/tests')
    ->notPath(__DIR__.'/tests/Fixtures')
;

return PhpCsFixer\Config::create()
    ->setCacheFile(__DIR__.'/.github/.cache/php-cs-fixer/.php_cs.cache')
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR2' => true,
        '@Symfony' => true,
    ])
    ->setFinder($finder)
;
