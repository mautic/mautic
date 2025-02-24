<?php

namespace Mautic\LeadBundle\Model;

class SegmentActionModel
{
    public function __construct(
        private LeadModel $contactModel
    ) {
    }

    public function addContacts(array $contactIds, array $segmentIds): void
    {
        $contacts = $this->contactModel->getLeadsByIds($contactIds);

        foreach ($contacts as $contact) {
            if (!$this->contactModel->canEditContact($contact)) {
                continue;
            }

            $this->contactModel->addToLists($contact, $segmentIds);
        }

        $this->contactModel->saveEntities($contacts);
    }

    public function removeContacts(array $contactIds, array $segmentIds): void
    {
        $contacts = $this->contactModel->getLeadsByIds($contactIds);

        foreach ($contacts as $contact) {
            if (!$this->contactModel->canEditContact($contact)) {
                continue;
            }

            $this->contactModel->removeFromLists($contact, $segmentIds);
        }

        $this->contactModel->saveEntities($contacts);
    }
}
