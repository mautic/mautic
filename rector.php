<?php

declare(strict_types=1);

use Rector\Caching\ValueObject\Storage\FileCacheStorage;
use Rector\Core\Configuration\Option;

return static function (Rector\Config\RectorConfig $rectorConfig): void {
    $rectorConfig->paths([__DIR__.'/app/bundles', __DIR__.'/plugins']);
    $rectorConfig->skip(
        [
            __DIR__.'/*/test/*',
            __DIR__.'/*/tests/*',
            __DIR__.'/*/Test/*',
            __DIR__.'/*/Tests/*',
            __DIR__.'/*.html.php',
            __DIR__.'/*.less.php',
            __DIR__.'/*.inc.php',
            __DIR__.'/*.js.php',
        ]
    );

    $rectorConfig->parallel();
    $rectorConfig->parameters()->set(Option::CACHE_CLASS, FileCacheStorage::class);
    $rectorConfig->parameters()->set(Option::CACHE_DIR, __DIR__.'/var/cache/rector');

    // Define what rule sets will be applied
    // $rectorConfig->sets([\Rector\Set\ValueObject\SetList::DEAD_CODE]); // @todo implement the whole set. Start rule by rule bellow.

    // Define what signle rules will be applied
    $rectorConfig->rule(\Rector\DeadCode\Rector\BooleanAnd\RemoveAndTrueRector::class);
    $rectorConfig->rule(\Rector\DeadCode\Rector\Stmt\RemoveUnreachableStatementRector::class);
    $rectorConfig->rule(\Rector\DeadCode\Rector\ClassConst\RemoveUnusedPrivateClassConstantRector::class);
    $rectorConfig->rule(\Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPrivateMethodParameterRector::class);

    // temp workaround to prevent rector to fail due to an undefined const.
    // This doesn't make much sense, and is probably fixed in a more recent version of Rector.
    if (!defined('MAUTIC_TABLE_PREFIX')) {
        //set the table prefix before boot
        define('MAUTIC_TABLE_PREFIX', '');
    }
};
