<?php

declare(strict_types=1);

use Symplify\EasyCodingStandard\Config\ECSConfig;
use Utils\ECS\StandaloneLineAutowireParamFixer;

return ECSConfig::configure()
    ->withRules([StandaloneLineAutowireParamFixer::class]);
