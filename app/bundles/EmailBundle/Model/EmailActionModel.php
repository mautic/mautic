<?php

namespace Mautic\EmailBundle\Model;

use Mautic\LeadBundle\Model\LeadModel;

class EmailActionModel
{
    public function __construct(
        private LeadModel $contactModel
    ) {
    }

    public function addEmailsToCategories(array $contactIds, array $categoryIds): void
    {
        $contacts = $this->contactModel->getLeadsByIds($contactIds);

        foreach ($contacts as $contact) {
            if (!$this->contactModel->canEditContact($contact)) {
                continue;
            }

            $this->contactModel->addToCategory($contact, $categoryIds);
        }
    }

    public function removeEmailsFromCategories(array $contactIds, array $categoryIds): void
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
