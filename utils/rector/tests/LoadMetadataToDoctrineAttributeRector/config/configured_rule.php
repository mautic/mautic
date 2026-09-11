<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\AddDoctrineOrmMappingAliasImportRector;
use Utils\Rector\LoadMetadataMauticHelperToAttributeRector;
use Utils\Rector\LoadMetadataToDoctrineAttributeRector;

// The Mautic helper rule desugars convenience helpers into native builder calls; the attribute rule
// then converts those, and the import rule adds the ORM alias the emitted attributes rely on.
return RectorConfig::configure()
    ->withRules([
        LoadMetadataMauticHelperToAttributeRector::class,
        LoadMetadataToDoctrineAttributeRector::class,
        AddDoctrineOrmMappingAliasImportRector::class,
    ]);
