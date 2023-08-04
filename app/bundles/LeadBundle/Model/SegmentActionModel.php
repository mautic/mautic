<?php

namespace Mautic\LeadBundle\Model;

class SegmentActionModel
{
    /**
     * @var LeadModel
     */
    private $contactModel;

    public function __construct(LeadModel $contactModel)
    {
        $this->contactModel = $contactModel;
    }

    public function addContacts(array $contactIds, array $segmentIds)
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

    public function removeContacts(array $contactIds, array $segmentIds)
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
