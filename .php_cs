<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__.'/app/bundles')
    ->in(__DIR__.'/app/config')
    ->in(__DIR__.'/app/middlewares')
    ->in(__DIR__.'/app/migrations')
    ->in(__DIR__.'/plugins')
    ->in(__DIR__.'/.github/workflows/mautic-asset-upload');

return PhpCsFixer\Config::create()
    ->setRules([
        '@Symfony'               => true,
        'binary_operator_spaces' => [
            'align_double_arrow' => true,
            'align_equals'       => true,
        ],
        'phpdoc_to_comment' => false,
        'ordered_imports'   => true,
        'array_syntax'      => [
            'syntax' => 'short',
        ],
        'no_unused_imports' => false,
    ])
    ->setFinder($finder);
