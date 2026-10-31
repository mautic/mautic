<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\GetRepositoryToRepositoryServiceRector;

return RectorConfig::configure()
    ->withRules([GetRepositoryToRepositoryServiceRector::class]);
