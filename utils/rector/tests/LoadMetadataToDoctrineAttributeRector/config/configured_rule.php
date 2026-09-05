<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\LoadMetadataToDoctrineAttributeRector;

return RectorConfig::configure()
    ->withRules([LoadMetadataToDoctrineAttributeRector::class]);
