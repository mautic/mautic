<?php

namespace Mautic\LeadBundle\Segment\IntegrationCampaign;

class IntegrationCampaignParts
{
    private string $integrationName;

    private string $campaignId;

    /**
     * @param string $field
     */
    public function __construct($field)
    {
        if (str_contains($field, '::')) {
            [$integrationName, $campaignId] = explode('::', $field);
        } else {
            // Assuming this is a Salesforce integration for BC with pre 2.11.0
            $integrationName = 'Salesforce';
            $campaignId      = $field;
        }
        $this->integrationName = $integrationName;
        $this->campaignId      = $campaignId;
    }

    public function getIntegrationName(): string
    {
        return $this->integrationName;
    }

    public function getCampaignId(): string
    {
        return $this->campaignId;
    }
}
