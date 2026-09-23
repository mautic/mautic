<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\TestGetRepositoryToContainerGetRector;

return RectorConfig::configure()
    ->withRules([TestGetRepositoryToContainerGetRector::class]);
