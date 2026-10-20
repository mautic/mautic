<?php

require 'autoload.php';

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__.'/app/bundles')
    ->in(__DIR__.'/app/config')
    ->in(__DIR__.'/app/middlewares')
    ->in(__DIR__.'/app/migrations')
    ->in(__DIR__.'/plugins')
    ->in(__DIR__.'/tests')
    ->in(__DIR__.'/utils')
    // rector rule fixtures are test data, not code, and reformatting them breaks the expected output
    ->notName('*.php.inc')
    ->in(__DIR__.'/.github/workflows/mautic-asset-upload')
    ->exclude('_support/_generated')
    ->exclude('node_modules')
    ->append([
        __DIR__.'/app/AppKernel.php',
        __DIR__.'/app/AppTestKernel.php',
        __DIR__.'/rector.php',
        __DIR__.'/.php-cs-fixer.php',
        __DIR__.'/ecs.php',
    ]);

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony'               => true,
        'binary_operator_spaces' => [
            'operators' => [
                '=>' => null,
                '='  => null,
            ],
        ],
        'phpdoc_to_comment' => false,
        'ordered_imports'   => true,
        'array_syntax'      => [
            'syntax' => 'short',
        ],
        'no_unused_imports' => true,
        /**
         * Our templates rely heavily on things like endforeach, endif, etc.
         * This setting should be turned off at least until we've switched to Twig
         * (which is required for Symfony 5).
         */
        'no_alternative_syntax' => false,
        'header_comment'        => [
            'header' => '',
        ],
        'multiline_whitespace_before_semicolons'           => true,
        'nullable_type_declaration_for_default_null_value' => true,
        'new_with_parentheses'                             => ['anonymous_class' => true],
        'no_superfluous_phpdoc_tags'                       => [
            'allow_mixed' => true,
        ],
    ])
    ->setFinder($finder);
