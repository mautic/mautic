<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Segment\IntegrationCampaign;

class IntegrationCampaignParts
{
    /**
     * @var string
     */
    private $integrationName;

    /**
     * @var string
     */
    private $campaignId;

    /**
     * @param string $field
     */
    public function __construct($field)
    {
        if (strpos($field, '::') !== false) {
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
