<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\LoadMetadataStaticHelperToAttributeRector;

return RectorConfig::configure()
    ->withRules([LoadMetadataStaticHelperToAttributeRector::class]);
