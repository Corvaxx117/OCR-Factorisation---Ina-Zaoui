<?php

$finder = (new PhpCsFixer\Finder())
    ->in([
        __DIR__.'/bin',
        __DIR__.'/config',
        __DIR__.'/migrations',
        __DIR__.'/public',
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
    ->notPath([
        'config/bundles.php',
        'config/reference.php',
    ])
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
    ])
    ->setFinder($finder)
    ->setRiskyAllowed(false)
;
