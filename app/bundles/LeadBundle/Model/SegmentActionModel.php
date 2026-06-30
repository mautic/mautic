<?php

namespace Mautic\LeadBundle\Model;

class SegmentActionModel
{
    public const LOAD_RESULTS_IN_CHUNKS_OF = 100;

    public function __construct(
        private LeadModel $contactModel,
    ) {
    }

    public function addContacts(array $contactIds, array $segmentIds): void
    {
        $contacts = $this->contactModel->getLeadsByIds($contactIds, true);
        // Do this in chunks so that we don't run out of memory.
        $chunks = array_chunk($contacts, self::LOAD_RESULTS_IN_CHUNKS_OF);
        foreach ($chunks as $chunk) {
            foreach ($chunk as $contact) {
                if (!$this->contactModel->canEditContact($contact)) {
                    continue;
                }

                $this->contactModel->addToLists($contact, $segmentIds);
            }
            // Clear the chunk from memory after each iteration.
            unset($chunk);
        }
        $this->contactModel->saveEntities($contacts);
    }

    public function removeContacts(array $contactIds, array $segmentIds): void
    {
        $contacts = $this->contactModel->getLeadsByIds($contactIds, true);
        // Do this in chunks so that we don't run out of memory.
        $chunks = array_chunk($contacts, self::LOAD_RESULTS_IN_CHUNKS_OF);
        foreach ($chunks as $chunk) {
            foreach ($chunk as $contact) {
                if (!$this->contactModel->canEditContact($contact)) {
                    continue;
                }
                $this->contactModel->removeFromLists($contact, $segmentIds);
            }
            // Clear the chunk from memory after each iteration.
            unset($chunk);
        }
        $this->contactModel->saveEntities($contacts);
    }
}
