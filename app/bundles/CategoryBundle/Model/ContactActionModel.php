<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CategoryBundle\Model;

use Mautic\LeadBundle\Model\LeadModel;

class ContactActionModel
{
    /**
     * @var LeadModel
     */
    private $contactModel;

    /**
     * @param LeadModel $contactModel
     */
    public function __construct(LeadModel $contactModel)
    {
        $this->contactModel = $contactModel;
    }

    /**
     * @param array $contactIds
     * @param array $categoryIds
     */
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

    /**
     * @param array $contactIds
     * @param array $categoryIds
     */
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
