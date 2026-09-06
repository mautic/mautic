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
        PhpCsFixer\Fixer\Phpdoc\PhpdocToCommentFixer::class,

        // conflicts with Utils/no_blank_line_between_imports, which keeps imports in one block
        PhpCsFixer\Fixer\Whitespace\BlankLineBetweenImportGroupsFixer::class,
    ])
    ->withConfiguredRule(PhpCsFixer\Fixer\Phpdoc\NoSuperfluousPhpdocTagsFixer::class, [
        'allow_mixed' => true,
    ])
    ->withConfiguredRule(PhpCsFixer\Fixer\Operator\NewWithParenthesesFixer::class, [
        'anonymous_class' => true,
    ])
    ->withRules([
        PhpCsFixer\Fixer\Semicolon\MultilineWhitespaceBeforeSemicolonsFixer::class,
        PhpCsFixer\Fixer\FunctionNotation\NullableTypeDeclarationForDefaultNullValueFixer::class,
        Utils\ECS\Fixer\NoBlankLineBetweenImportsFixer::class,
    ])
    ->withPhpCsFixerSets(symfony: true)
    ->withPreparedSets(
        comments: true,
        docblocks: true,
        namespaces: true,
        cleanup: true,
        controlStructures: true,
        standaloneLine: true,
    );
