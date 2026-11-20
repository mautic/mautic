<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\Integration;

use Mautic\IntegrationsBundle\Integration\BasicIntegration;
use Mautic\IntegrationsBundle\Integration\Interfaces\BasicInterface;

class TwilioIntegration extends BasicIntegration implements BasicInterface
{
    public const string NAME = 'Twilio';

    public function getName(): string
    {
        return self::NAME;
    }

    public function getDisplayName(): string
    {
        return self::NAME;
    }

    public function getIcon(): string
    {
        return 'app/bundles/SmsBundle/Assets/img/Twilio.png';
    }

    /**
     * Settings saved by the "Features" tab (messaging_service_sid, frequency_number, frequency_time).
     *
     * @return array<string, mixed>
     */
    public function getIntegrationFeatureSettings(): array
    {
        return $this->getIntegrationSettings()?->getFeatureSettings()['integration'] ?? [];
    }
}
