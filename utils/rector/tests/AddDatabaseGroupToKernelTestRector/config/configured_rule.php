<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\AddDatabaseGroupToKernelTestRector;

return RectorConfig::configure()
    ->withRules([AddDatabaseGroupToKernelTestRector::class]);
