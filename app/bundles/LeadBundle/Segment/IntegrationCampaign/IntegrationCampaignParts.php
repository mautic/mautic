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
        if (false !== strpos($field, '::')) {
            list($integrationName, $campaignId) = explode('::', $field);
        } else {
            // Assuming this is a Salesforce integration for BC with pre 2.11.0
            $integrationName = 'Salesforce';
            $campaignId      = $field;
        }
        $this->integrationName = $integrationName;
        $this->campaignId      = $campaignId;
    }

    /**
     * @return string
     */
    public function getIntegrationName()
    {
        return $this->integrationName;
    }

    /**
     * @return string
     */
    public function getCampaignId()
    {
        return $this->campaignId;
    }
}
