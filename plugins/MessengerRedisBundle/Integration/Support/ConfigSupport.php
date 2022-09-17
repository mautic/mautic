<?php

declare(strict_types=1);

namespace MauticPlugin\MessengerRedisBundle\Integration\Support;

use Mautic\IntegrationsBundle\Integration\DefaultConfigFormTrait;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormInterface;
use MauticPlugin\MessengerRedisBundle\Integration\MessengerRedisIntegration;

class ConfigSupport extends MessengerRedisIntegration implements ConfigFormInterface
{
    use DefaultConfigFormTrait;
}
