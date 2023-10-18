<?php

declare(strict_types=1);

namespace MauticPlugin\MauticSocialBundle\Integration;

use Mautic\PluginBundle\Helper\IntegrationHelper;

final class Config
{
    public function __construct(private IntegrationHelper $integrationsHelper)
    {
    }

    public function isPublished(): bool
    {
        $integration = $this->integrationsHelper->getIntegrationObject(TwitterIntegration::NAME);

        return $integration && $integration->getIntegrationSettings()->getIsPublished();
    }
}
