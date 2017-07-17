<?php

namespace MauticPlugin\MauticCrmBundle\Api;

use MauticPlugin\MauticCrmBundle\Integration\CrmAbstractIntegration;

class CrmApi
{
    protected $integration;

    public function __construct(CrmAbstractIntegration $integration)
    {
        $this->integration = $integration;
    }
}
