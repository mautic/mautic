<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__.'/app/bundles')
    ->exclude('CoreBundle/Tests/_support/_generated')
    ->in(__DIR__.'/app/config')
    ->in(__DIR__.'/app/middlewares')
    ->in(__DIR__.'/app/migrations')
    ->in(__DIR__.'/plugins')
    ->in(__DIR__.'/.github/workflows/mautic-asset-upload');
    

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony'               => true,
        'binary_operator_spaces' => [
            'operators' => [
                '=>' => 'align',
                '=' => 'align'
            ]
        ],
        'phpdoc_to_comment' => false,
        'ordered_imports'   => true,
        'array_syntax'      => [
            'syntax' => 'short',
        ],
        'no_unused_imports' => true,
    ])
    ->setFinder($finder);
