<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\LoadMetadataClassToDoctrineAttributeRector;
use Utils\Rector\LoadMetadataFieldToDoctrineAttributeRector;
use Utils\Rector\LoadMetadataIndexToDoctrineAttributeRector;
use Utils\Rector\LoadMetadataLifecycleToDoctrineAttributeRector;
use Utils\Rector\LoadMetadataMauticHelperToAttributeRector;
use Utils\Rector\LoadMetadataRepositoryToDoctrineAttributeRector;
use Utils\Rector\LoadMetadataTableToDoctrineAttributeRector;

// The Mautic helper rule desugars convenience helpers into native builder calls; the six per-concern
// attribute rules then convert those. The field rule runs first so its hybrid detection sees only the
// class' pre-existing attributes, and the class rule runs last so the mapped-superclass root is set
// before the setMappedSuperClass() call is trimmed. Together they cover the full conversion.
return RectorConfig::configure()
    ->withRules([
        LoadMetadataMauticHelperToAttributeRector::class,
        LoadMetadataFieldToDoctrineAttributeRector::class,
        LoadMetadataTableToDoctrineAttributeRector::class,
        LoadMetadataRepositoryToDoctrineAttributeRector::class,
        LoadMetadataIndexToDoctrineAttributeRector::class,
        LoadMetadataLifecycleToDoctrineAttributeRector::class,
        LoadMetadataClassToDoctrineAttributeRector::class,
    ]);
