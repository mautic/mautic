<?php

namespace MauticPlugin\MauticEmailMarketingBundle\Api;

use Mautic\PluginBundle\Integration\AbstractIntegration;
use Mautic\PluginBundle\Integration\UnifiedIntegrationInterface;

class EmailMarketingApi
{
    /**
     * @var AbstractIntegration
     */
    protected \Mautic\PluginBundle\Integration\UnifiedIntegrationInterface $integration;
    protected $keys;

    /**
     * @param AbstractIntegration $integration
     */
    public function __construct(UnifiedIntegrationInterface $integration)
    {
        $this->integration = $integration;
        $this->keys        = $integration->getKeys();
    }
}
