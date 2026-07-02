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
<<<<<<< HEAD
<<<<<<< HEAD
    ->withDocblockLevel(17);
=======
<<<<<<< HEAD
    ->withDocblockLevel(8);
=======
<<<<<<< HEAD
    ->withDocblockLevel(1);
=======
    ->withDocblockLevel(9);
>>>>>>> 3d6b9d54a7 (next)
>>>>>>> 54e9225ef7 (next)
>>>>>>> 7706949fdb (next)
=======
    ->withDocblockLevel(11);
>>>>>>> 809ff8fa6a ([cs] flip variable name and type)
// ->withPreparedSets(comments: true);
