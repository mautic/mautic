<?php

namespace MauticPlugin\MauticCrmBundle\Api;

use MauticPlugin\MauticCrmBundle\Integration\CrmAbstractIntegration;

/**
 * @method createLead(array<string, mixed> $fields, $lead)
 */
class CrmApi
{
    public function __construct(
        protected CrmAbstractIntegration $integration,
    ) {
    }
}
