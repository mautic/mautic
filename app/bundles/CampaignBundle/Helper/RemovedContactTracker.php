<?php

namespace Mautic\CampaignBundle\Helper;

class RemovedContactTracker
{
    /**
     * @var array
     */
    private $removedContacts = [];

    /**
     * @param int $campaignId
     * @param int $contactId
     */
    public function addRemovedContact($campaignId, $contactId)
    {
        if (!isset($this->removedContacts[$campaignId])) {
            $this->removedContacts[$campaignId] = [];
        }

        $this->removedContacts[$campaignId][$contactId] = $contactId;
    }

    /**
     * @param int   $campaignId
     * @param array $contacts
     */
    public function addRemovedContacts($campaignId, array $contactIds)
    {
        foreach ($contactIds as $contactId) {
            $this->addRemovedContact($campaignId, $contactId);
        }
    }

    /**
     * @param int $campaignId
     */
    public function clearRemovedContact($campaignId, $contactId)
    {
        unset($this->removedContacts[$campaignId][$contactId]);
    }

    /**
     * @param int $campaignId
     */
    public function wasContactRemoved($campaignId, $contactId)
    {
        return !empty($this->removedContacts[$campaignId][$contactId]);
    }

    /**
     * @return array
     */
    public function getRemovedContacts()
    {
        return $this->removedContacts;
    }
}
