<?php

namespace Mautic\CategoryBundle\Model;

use Mautic\LeadBundle\Model\LeadModel;

class ContactActionModel
{
    /**
     * @var LeadModel
     */
    private $contactModel;

    public function __construct(LeadModel $contactModel)
    {
        $this->contactModel = $contactModel;
    }

    public function addContactsToCategories(array $contactIds, array $categoryIds)
    {
        $contacts = $this->contactModel->getLeadsByIds($contactIds);

        foreach ($contacts as $contact) {
            if (!$this->contactModel->canEditContact($contact)) {
                continue;
            }

            $this->contactModel->addToCategory($contact, $categoryIds);
        }
    }

    public function removeContactsFromCategories(array $contactIds, array $categoryIds)
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
