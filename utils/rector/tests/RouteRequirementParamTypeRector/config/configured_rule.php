<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\RouteRequirementParamTypeRector;

return RectorConfig::configure()
    ->withRules([RouteRequirementParamTypeRector::class]);
