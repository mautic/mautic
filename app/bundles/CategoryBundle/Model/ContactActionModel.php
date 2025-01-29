<?php

namespace Mautic\CategoryBundle\Model;

use Mautic\LeadBundle\Model\LeadModel;

class ContactActionModel
{
    public function __construct(
        private LeadModel $contactModel,
    ) {
    }

    public function addContactsToCategories(array $contactIds, array $categoryIds): void
    {
        $contacts = $this->contactModel->getLeadsByIds($contactIds);

        foreach ($contacts as $contact) {
            if (!$this->contactModel->canEditContact($contact)) {
                continue;
            }

            $this->contactModel->addToCategory($contact, $categoryIds);
        }
    }

    public function removeContactsFromCategories(array $contactIds, array $categoryIds): void
    {
        $contacts = $this->contactModel->getLeadsByIds($contactIds);

        foreach ($contacts as $contact) {
            if (!$this->contactModel->canEditContact($contact)) {
                continue;
            }

            $contactCategoryRelations = $this->contactModel->getLeadCategories($contact);
            $relationsToDelete        = array_intersect($contactCategoryRelations, $categoryIds);
            $this->contactModel->removeFromCategories($relationsToDelete);
        }
    }
}
