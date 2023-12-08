<?php

declare(strict_types=1);

namespace MauticPlugin\GrapesJsBuilderBundle\Integration;

use Mautic\IntegrationsBundle\Exception\IntegrationNotFoundException;
use Mautic\IntegrationsBundle\Helper\IntegrationsHelper;
use Mautic\PluginBundle\Entity\Integration;

class Config
{
<<<<<<< HEAD
    public function __construct(private IntegrationsHelper $integrationsHelper)
=======
    private \Mautic\IntegrationsBundle\Helper\IntegrationsHelper $integrationsHelper;

    public function __construct(IntegrationsHelper $integrationsHelper)
>>>>>>> 11b4805f88 ([type-declarations] Re-run rector rules on plugins, Report, Sms, User, Lead, Dynamic, Config bundles)
    {
    }

    public function isPublished(): bool
    {
        try {
            $integration = $this->getIntegrationEntity();

            return (bool) $integration->getIsPublished() ?: false;
        } catch (IntegrationNotFoundException) {
            return false;
        }
    }

    /**
     * @return mixed[]
     */
    public function getFeatureSettings(): array
    {
        try {
            $integration = $this->getIntegrationEntity();

            return $integration->getFeatureSettings() ?: [];
        } catch (IntegrationNotFoundException) {
            return [];
        }
    }

    /**
     * @throws IntegrationNotFoundException
     */
    public function getIntegrationEntity(): Integration
    {
        $integrationObject = $this->integrationsHelper->getIntegration(GrapesJsBuilderIntegration::NAME);

        return $integrationObject->getIntegrationConfiguration();
    }
}
