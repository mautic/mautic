<?php

namespace MauticPlugin\MauticCitrixBundle\Api;

use MauticPlugin\MauticCitrixBundle\Integration\CitrixAbstractIntegration;

class CitrixApi
{
    protected $integration;

    public function __construct(CitrixAbstractIntegration $integration)
    {
        $this->integration = $integration;
    }
}
