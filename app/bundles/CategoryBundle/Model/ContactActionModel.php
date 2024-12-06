<?php

namespace Mautic\CategoryBundle\Model;

use Mautic\LeadBundle\Model\LeadModel;

class ContactActionModel
{
    public const LOAD_RESULTS_IN_CHUNKS_OF = 200;

    public function __construct(
        private LeadModel $contactModel,
    ) {
    }

    public function addContactsToCategories(array $contactIds, array $categoryIds): void
    {
        $contacts = $this->contactModel->getLeadsByIds($contactIds, true);
        // Do this in chunks so that we don't run out of memory.
        $chunks = array_chunk($contacts, self::LOAD_RESULTS_IN_CHUNKS_OF);
        foreach ($chunks as $chunk) {
            foreach ($chunk as $contact) {
                if (!$this->contactModel->canEditContact($contact)) {
                    continue;
                }
                $this->contactModel->addToCategory($contact, $categoryIds);
            }
            // Clear the chunk from memory after each iteration.
            unset($chunk);
        }
    }

    public function removeContactsFromCategories(array $contactIds, array $categoryIds): void
    {
        $contacts = $this->contactModel->getLeadsByIds($contactIds, true);
        // Do this in chunks so that we don't run out of memory.
        $chunks = array_chunk($contacts, self::LOAD_RESULTS_IN_CHUNKS_OF);
        foreach ($chunks as $chunk) {
            foreach ($chunk as $contact) {
                if (!$this->contactModel->canEditContact($contact)) {
                    continue;
                }

                $contactCategoryRelations = $this->contactModel->getLeadCategories($contact);
                $relationsToDelete        = array_intersect($contactCategoryRelations, $categoryIds);
                $this->contactModel->removeFromCategories($relationsToDelete);
            }
            // Clear the chunk from memory after each iteration.
            unset($chunk);
        }
    }
}
