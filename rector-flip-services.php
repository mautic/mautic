<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\ConfigServiceToAutowiredServiceRector;

// standalone config: flips leftover "services" keys from bundle Config/config.php
// to the autowired Config/services.php next to them
return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/app/bundles/AssetBundle/Config/config.php',
        // __DIR__.'/app/bundles/CampaignBundle/Config/config.php',
        __DIR__.'/app/bundles/CoreBundle/Config/config.php',
        // __DIR__.'/app/bundles/FormBundle/Config/config.php',
        __DIR__.'/app/bundles/LeadBundle/Config/config.php',
//        __DIR__.'/app/bundles/SmsBundle/Config/config.php',
        __DIR__.'/app/bundles/UserBundle/Config/config.php',
    ])
    ->withRules([
        ConfigServiceToAutowiredServiceRector::class,
    ]);
