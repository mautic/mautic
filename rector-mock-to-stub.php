<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\PHPUnit\Set\PHPUnitSetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/app/bundles/*/Test',
        __DIR__.'/app/bundles/*/Tests',
        __DIR__.'/plugins/*/Test',
        __DIR__.'/plugins/*/Tests',
    ])
    ->withSets([
        PHPUnitSetList::PHPUNIT_MOCK_TO_STUB,
    ]);
