<?php

declare(strict_types=1);

namespace MauticPlugin\MauticSocialBundle\Integration;

use Mautic\PluginBundle\Helper\IntegrationHelper;

final class Config
{
    private IntegrationHelper $integrationsHelper;

    public function __construct(IntegrationHelper $integrationsHelper)
    {
        $this->integrationsHelper = $integrationsHelper;
    }

    public function isPublished(): bool
    {
        $integration = $this->integrationsHelper->getIntegrationObject(TwitterIntegration::NAME);

        return $integration && $integration->getIntegrationSettings()->getIsPublished();
    }
}
