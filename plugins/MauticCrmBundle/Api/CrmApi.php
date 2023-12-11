<?php

namespace MauticPlugin\MauticCrmBundle\Api;

use MauticPlugin\MauticCrmBundle\Integration\CrmAbstractIntegration;

/**
 * Class CrmApi.
 *
 * @method createLead()
 */
class CrmApi
{
    public function __construct(protected CrmAbstractIntegration $integration)
    {
    }
}
