<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\ModelGetRepositoryToRepositoryServiceRector;

return RectorConfig::configure()
    ->withRules([ModelGetRepositoryToRepositoryServiceRector::class]);
