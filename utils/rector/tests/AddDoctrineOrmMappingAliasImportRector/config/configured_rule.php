<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\AddDoctrineOrmMappingAliasImportRector;

return RectorConfig::configure()
    ->withRules([
        AddDoctrineOrmMappingAliasImportRector::class,
    ]);
