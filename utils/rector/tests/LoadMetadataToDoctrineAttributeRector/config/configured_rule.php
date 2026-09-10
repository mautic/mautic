<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\LoadMetadataMauticHelperToAttributeRector;
use Utils\Rector\LoadMetadataToDoctrineAttributeRector;

// The Mautic helper rule desugars convenience helpers into native builder calls; the attribute rule
// then converts those. They run together here so the fixtures cover the full conversion.
return RectorConfig::configure()
    ->withRules([
        LoadMetadataMauticHelperToAttributeRector::class,
        LoadMetadataToDoctrineAttributeRector::class,
    ]);
