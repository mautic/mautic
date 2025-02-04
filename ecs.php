<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\Import\NoUnusedImportsFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

return function (ECSConfig $ecsConfig): void {
    $ecsConfig->paths([
        __DIR__.'/app',
        __DIR__.'/config',
        __DIR__.'/plugins',
        __DIR__.'/tests',
    ]);

    // this way you add a single rule
    $ecsConfig->rules([
        NoUnusedImportsFixer::class,
        Symplify\CodingStandard\Fixer\Spacing\StandaloneLinePromotedPropertyFixer::class,
        PhpCsFixer\Fixer\ClassNotation\ClassAttributesSeparationFixer::class,
    ]);

    // this way you can add sets - group of rules
    $ecsConfig->sets([
        // run and fix, one by one
        // SetList::SPACES,
        // SetList::ARRAY,
        SetList::DOCBLOCK,
        SetList::NAMESPACES,
        SetList::COMMENTS,
        // SetList::PSR_12,
    ]);
};
