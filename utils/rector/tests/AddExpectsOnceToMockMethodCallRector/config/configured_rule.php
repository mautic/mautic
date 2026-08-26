<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\AddExpectsOnceToMockMethodCallRector;

return RectorConfig::configure()
    ->withRules([AddExpectsOnceToMockMethodCallRector::class]);
