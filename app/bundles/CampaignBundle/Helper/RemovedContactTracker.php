<?php

namespace Mautic\CampaignBundle\Helper;

class RemovedContactTracker
{
    private array $removedContacts = [];

    /**
     * @param int $campaignId
     * @param int $contactId
     */
    public function addRemovedContact($campaignId, $contactId): void
    {
        if (!isset($this->removedContacts[$campaignId])) {
            $this->removedContacts[$campaignId] = [];
        }

        $this->removedContacts[$campaignId][$contactId] = $contactId;
    }

    /**
     * @param int $campaignId
     */
    public function addRemovedContacts($campaignId, array $contactIds): void
    {
        foreach ($contactIds as $contactId) {
            $this->addRemovedContact($campaignId, $contactId);
        }
    }

    /**
     * @param int $campaignId
     */
    public function clearRemovedContact($campaignId, $contactId): void
    {
        unset($this->removedContacts[$campaignId][$contactId]);
    }

    /**
     * @param int $campaignId
     */
    public function wasContactRemoved($campaignId, $contactId): bool
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
