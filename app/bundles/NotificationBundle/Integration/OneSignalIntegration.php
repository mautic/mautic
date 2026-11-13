<?php

declare(strict_types=1);

namespace Mautic\NotificationBundle\Integration;

use Mautic\IntegrationsBundle\Integration\BasicIntegration;
use Mautic\IntegrationsBundle\Integration\Interfaces\BasicInterface;

class OneSignalIntegration extends BasicIntegration implements BasicInterface
{
    public const string NAME = 'OneSignal';

    public const string FEATURE_MOBILE = 'mobile';

    public const string FEATURE_LANDING_PAGE_ENABLED = 'landing_page_enabled';

    public const string FEATURE_WELCOME_NOTIFICATION_ENABLED = 'welcome_notification_enabled';

    public const string FEATURE_TRACKING_PAGE_ENABLED = 'tracking_page_enabled';

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
        return 'app/bundles/NotificationBundle/Assets/img/OneSignal.png';
    }

    /**
     * Plain list of feature slugs: the legacy IntegrationHelper stores it as-is for a new install
     * and indexes features by value. The config form gets the slug => label map from ConfigSupport.
     *
     * @return string[]
     */
    public function getSupportedFeatures(): array
    {
        return [
            self::FEATURE_MOBILE,
            self::FEATURE_LANDING_PAGE_ENABLED,
            self::FEATURE_WELCOME_NOTIFICATION_ENABLED,
            self::FEATURE_TRACKING_PAGE_ENABLED,
        ];
    }

    /**
     * Settings saved by the "Features" tab (subdomain_name, platforms).
     *
     * @return array<string, mixed>
     */
    public function getIntegrationFeatureSettings(): array
    {
        return $this->getIntegrationSettings()?->getFeatureSettings()['integration'] ?? [];
    }
}
