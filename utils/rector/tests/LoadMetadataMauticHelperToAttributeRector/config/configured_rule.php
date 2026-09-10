<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\LoadMetadataMauticHelperToAttributeRector;

return RectorConfig::configure()
    ->withRules([LoadMetadataMauticHelperToAttributeRector::class]);
