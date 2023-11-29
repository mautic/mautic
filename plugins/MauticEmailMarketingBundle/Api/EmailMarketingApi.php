<?php

namespace MauticPlugin\MauticEmailMarketingBundle\Api;

use Mautic\PluginBundle\Integration\UnifiedIntegrationInterface;

/**
 * @template TIntegration as \Mautic\PluginBundle\Integration\UnifiedIntegrationInterface
 */
class EmailMarketingApi
{
    /**
     * @var TIntegration
     */
    protected \Mautic\PluginBundle\Integration\UnifiedIntegrationInterface $integration;

    protected $keys;

    /**
     * @param TIntegration $integration
     */
    public function __construct(UnifiedIntegrationInterface $integration)
    {
        $this->integration = $integration;
        $this->keys        = $integration->getKeys();
    }
}
