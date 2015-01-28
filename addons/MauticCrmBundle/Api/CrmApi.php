<?php

namespace MauticAddon\MauticCrmBundle\Api;

use MauticAddon\MauticCrmBundle\Integration\CrmAbstractIntegration;

class CrmApi
{

    protected $integration;

    public function __construct(CrmAbstractIntegration $integration)
    {
        $this->integration = $integration;
    }
}