<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\UnserializeToSerializerDecodeRector;

return RectorConfig::configure()
    ->withRules([UnserializeToSerializerDecodeRector::class]);
