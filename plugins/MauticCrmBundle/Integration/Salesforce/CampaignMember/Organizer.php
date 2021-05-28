<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticCrmBundle\Integration\Salesforce\CampaignMember;

use MauticPlugin\MauticCrmBundle\Integration\Salesforce\Object\Contact;
use MauticPlugin\MauticCrmBundle\Integration\Salesforce\Object\Lead;

class Organizer
{
    /**
     * @var array
     */
    private $records;

    /**
     * @var array
     */
    private $leads = [];

    /**
     * @var array
     */
    private $contacts = [];

    /**
     * ObjectOrganizer constructor.
     */
    public function __construct(array $records)
    {
        $this->records = $records;

        $this->organize();
    }

    /**
     * @return array
     */
    public function getLeads()
    {
        return $this->leads;
    }

    /**
     * @return array
     */
    public function getLeadIds()
    {
        return array_keys($this->leads);
    }

    /**
     * @return array
     */
    public function getContacts()
    {
        return $this->contacts;
    }

    /**
     * @return array
     */
    public function getContactIds()
    {
        return array_keys($this->contacts);
    }

    private function organize()
    {
        foreach ($this->records as $campaignMember) {
            $object    = !empty($campaignMember['LeadId']) ? 'Lead' : 'Contact';
            $objectId  = !empty($campaignMember['LeadId']) ? $campaignMember['LeadId'] : $campaignMember['ContactId'];
            $isDeleted = ($campaignMember['IsDeleted']) ? true : false;

            switch ($object) {
                case Lead::OBJECT:
                    $this->leads[$objectId] = new Lead($objectId, $campaignMember['CampaignId'], $isDeleted);
                    break;

                case Contact::OBJECT:
                    $this->contacts[$objectId] = new Contact($objectId, $campaignMember['CampaignId'], $isDeleted);
                    break;
            }
        }
    }
}
