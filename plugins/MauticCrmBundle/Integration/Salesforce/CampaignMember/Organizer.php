<?php

namespace MauticPlugin\MauticCrmBundle\Integration\Salesforce\CampaignMember;

use MauticPlugin\MauticCrmBundle\Integration\Salesforce\Object\Contact;
use MauticPlugin\MauticCrmBundle\Integration\Salesforce\Object\Lead;

class Organizer
{
    private array $leads = [];

    private array $contacts = [];

    public function __construct(
        private array $records
    ) {
        $this->organize();
    }

    /**
     * @return array
     */
    public function getLeads()
    {
        return $this->leads;
    }

    public function getLeadIds(): array
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

    public function getContactIds(): array
    {
        return array_keys($this->contacts);
    }

    private function organize(): void
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
