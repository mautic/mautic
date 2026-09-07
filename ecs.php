<?php

declare(strict_types=1);

use Symplify\EasyCodingStandard\Config\ECSConfig;

return ECSConfig::configure()
    ->withPaths([
        __DIR__.'/app',
        __DIR__.'/config',
        __DIR__.'/plugins',
        __DIR__.'/tests',
        __DIR__.'/utils',
    ])
    ->withRootFiles()
    ->withSkip([
        '*/node_modules/*',
        // test fixtures are data, reformatting them shifts line numbers asserted in rule tests
        '*/Fixture/*',
        PhpCsFixer\Fixer\Phpdoc\PhpdocNoEmptyReturnFixer::class => [
            // in docbclock on purpose, to avoid BC return on child classes
            __DIR__.'/app/bundles/CoreBundle/Entity/CommonEntity.php',
        ],

        PhpCsFixer\Fixer\Phpdoc\GeneralPhpdocAnnotationRemoveFixer::class,
        PhpCsFixer\Fixer\Operator\BinaryOperatorSpacesFixer::class,
        PhpCsFixer\Fixer\Operator\ConcatSpaceFixer::class,
        PhpCsFixer\Fixer\Operator\NotOperatorWithSpaceFixer::class,
        PhpCsFixer\Fixer\Operator\NotOperatorWithSuccessorSpaceFixer::class,
        PhpCsFixer\Fixer\Whitespace\MethodChainingIndentationFixer::class,
        Symplify\CodingStandard\Fixer\Spacing\MethodChainingNewlineFixer::class,
        PhpCsFixer\Fixer\ControlStructure\YodaStyleFixer::class,

        // templates rely on alternative syntax (endforeach, endif), keep it as-is
        PhpCsFixer\Fixer\ControlStructure\NoAlternativeSyntaxFixer::class,
    ])
    // keep @Symfony import grouping (class, then function, then const), so no reordering happens
    ->withConfiguredRule(PhpCsFixer\Fixer\Import\OrderedImportsFixer::class, [
        'imports_order' => ['class', 'function', 'const'],
        'sort_algorithm' => 'alpha',
    ])
    ->withRules([
        PhpCsFixer\Fixer\Semicolon\MultilineWhitespaceBeforeSemicolonsFixer::class,
        Utils\ECS\Fixer\NoBlankLineBetweenImportsFixer::class,
    ])
    ->withPreparedSets(
        comments: true,
        docblocks: true,
        namespaces: true,
        cleanup: true,
        controlStructures: true,
        standaloneLine: true,
    );
