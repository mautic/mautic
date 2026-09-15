<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\LoadMetadataAssociationToDoctrineAttributeRector;
use Utils\Rector\LoadMetadataClassToDoctrineAttributeRector;
use Utils\Rector\LoadMetadataColumnToDoctrineAttributeRector;
use Utils\Rector\LoadMetadataIndexToDoctrineAttributeRector;
use Utils\Rector\LoadMetadataLifecycleToDoctrineAttributeRector;
use Utils\Rector\LoadMetadataManyToManyToDoctrineAttributeRector;
use Utils\Rector\LoadMetadataMauticHelperToAttributeRector;
use Utils\Rector\LoadMetadataRepositoryToDoctrineAttributeRector;
use Utils\Rector\LoadMetadataTableToDoctrineAttributeRector;

// The Mautic helper rule desugars convenience helpers into native builder calls; the per-concern
// attribute rules then convert those. Each rule converts only its own builder calls and leaves the
// rest, so together they cover the full conversion. The class rule runs last so the mapped-superclass
// root is set before the setMappedSuperClass() call is trimmed.
return RectorConfig::configure()
    ->withRules([
        LoadMetadataMauticHelperToAttributeRector::class,
        LoadMetadataColumnToDoctrineAttributeRector::class,
        LoadMetadataAssociationToDoctrineAttributeRector::class,
        LoadMetadataManyToManyToDoctrineAttributeRector::class,
        LoadMetadataTableToDoctrineAttributeRector::class,
        LoadMetadataRepositoryToDoctrineAttributeRector::class,
        LoadMetadataIndexToDoctrineAttributeRector::class,
        LoadMetadataLifecycleToDoctrineAttributeRector::class,
        LoadMetadataClassToDoctrineAttributeRector::class,
    ]);
