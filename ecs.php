<?php

declare(strict_types=1);

use Symplify\EasyCodingStandard\Config\ECSConfig;

return ECSConfig::configure()
    ->withPaths([
        __DIR__.'/app',
        __DIR__.'/config',
        __DIR__.'/plugins',
        __DIR__.'/tests',
    ])
    ->withRootFiles()
    ->withSkip([
        PhpCsFixer\Fixer\Phpdoc\PhpdocNoEmptyReturnFixer::class => [
            // in docbclock on purpose, to avoid BC return on child classes
            __DIR__.'/app/bundles/CoreBundle/Entity/CommonEntity.php',
        ],

        PhpCsFixer\Fixer\Comment\SingleLineCommentStyleFixer::class,
        PhpCsFixer\Fixer\Phpdoc\GeneralPhpdocAnnotationRemoveFixer::class,
        PhpCsFixer\Fixer\Operator\ConcatSpaceFixer::class,
        PhpCsFixer\Fixer\Operator\BinaryOperatorSpacesFixer::class,
        PhpCsFixer\Fixer\Operator\NotOperatorWithSpaceFixer::class,
        PhpCsFixer\Fixer\Operator\NotOperatorWithSuccessorSpaceFixer::class,
        PhpCsFixer\Fixer\Whitespace\MethodChainingIndentationFixer::class,
        Symplify\CodingStandard\Fixer\Spacing\MethodChainingNewlineFixer::class,
        PhpCsFixer\Fixer\ControlStructure\YodaStyleFixer::class,
    ])
    ->withRules([
        Symplify\CodingStandard\Fixer\Spacing\StandaloneLinePromotedPropertyFixer::class,
    ])
    ->withPreparedSets(
        comments: true,
        docblocks: true,
        namespaces: true,
        cleanup: true,
        controlStructures: true,
    );
