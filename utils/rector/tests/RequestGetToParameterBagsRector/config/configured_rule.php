<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\RequestGetToParameterBagsRector;

return RectorConfig::configure()
    ->withRules([RequestGetToParameterBagsRector::class]);
