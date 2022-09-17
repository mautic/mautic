<?php

declare(strict_types=1);

namespace MauticPlugin\MessengerRedisBundle\Integration;

use Mautic\IntegrationsBundle\Integration\BasicIntegration;
use Mautic\IntegrationsBundle\Integration\ConfigurationTrait;
use Mautic\IntegrationsBundle\Integration\Interfaces\BasicInterface;

class MessengerRedisIntegration extends BasicIntegration implements BasicInterface
{
    use ConfigurationTrait;

    public const NAME = 'MessengerRedis';

    public const DISPLAY_NAME = 'Messenger Redis';

    public function getName(): string
    {
        return self::NAME;
    }

    public function getDisplayName(): string
    {
        return self::DISPLAY_NAME;
    }

    public function getIcon(): string
    {
        return 'plugins/MessengerRedisBundle/Assets/img/redis.png';
    }
}
