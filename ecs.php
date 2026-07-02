<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\Import\NoUnusedImportsFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return ECSConfig::configure()
    ->withPaths([
        __DIR__.'/app',
        __DIR__.'/config',
        __DIR__.'/plugins',
        __DIR__.'/tests',
    ])
    ->withRootFiles()
    ->withRules([
        NoUnusedImportsFixer::class,
        // Symplify\CodingStandard\Fixer\Spacing\StandaloneLinePromotedPropertyFixer::class,
    ])
    ->withPreparedSets(comments: true)
    ->withDocblockLevel(17);
// ->withPreparedSets(comments: true);
