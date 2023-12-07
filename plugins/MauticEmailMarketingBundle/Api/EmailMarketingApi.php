<?php

namespace MauticPlugin\MauticEmailMarketingBundle\Api;

use Mautic\PluginBundle\Integration\UnifiedIntegrationInterface;

class EmailMarketingApi
{
    protected $keys;

    public function __construct(protected \Mautic\PluginBundle\Integration\UnifiedIntegrationInterface $integration)
    {
        $this->keys        = $integration->getKeys();
    }
}
