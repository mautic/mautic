<?php

namespace MauticPlugin\MauticEmailMarketingBundle\Api;

use Mautic\PluginBundle\Integration\AbstractIntegration;
use Mautic\PluginBundle\Integration\UnifiedIntegrationInterface;

class EmailMarketingApi
{
    protected $keys;

    /**
     * @param AbstractIntegration $integration
     */
    public function __construct(
        protected UnifiedIntegrationInterface $integration
    ) {
        $this->keys        = $integration->getKeys();
    }
}
