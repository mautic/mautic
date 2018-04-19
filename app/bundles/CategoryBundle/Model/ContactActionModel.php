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

use Doctrine\ORM\Tools\Pagination\Paginator;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;

class ContactActionModel
{
    /**
     * @var LeadModel
     */
    private $contactModel;

    /**
     * @var CorePermissions
     */
    private $permissions;

    /**
     * @param LeadModel       $contactModel
     * @param CorePermissions $permissions
     */
    public function __construct(LeadModel $contactModel, CorePermissions $permissions)
    {
        $this->contactModel = $contactModel;
        $this->permissions  = $permissions;
    }

    /**
     * @param array $contactIds
     * @param array $categoryIds
     */
    public function addContactsToCategories(array $contactIds, array $categoryIds)
    {
        $contacts = $this->loadLeads($contactIds);

        foreach ($contacts as $contact) {
            if (!$this->canEdit($contact)) {
                continue;
            }

            $this->contactModel->addToCategory($contact, $categoryIds);
            $this->contactModel->detachEntity($contact);
        }
    }

    /**
     * @param array $contactIds
     * @param array $categoryIds
     */
    public function removeContactsFromCategories(array $contactIds, array $categoryIds)
    {
        $contacts = $this->loadLeads($contactIds);

        foreach ($contacts as $contact) {
            if (!$this->canEdit($contact)) {
                continue;
            }

            $contactCategoryRelations = $this->contactModel->getLeadCategories($contact);
            $relationsToDelete        = array_intersect($contactCategoryRelations, $categoryIds);
            $this->contactModel->removeFromCategories($relationsToDelete);
            $this->contactModel->detachEntity($contact);
        }
    }

    /**
     * @param Lead $contact
     *
     * @return bool
     */
    private function canEdit(Lead $contact)
    {
        return $this->permissions->hasEntityAccess('lead:leads:editown', 'lead:leads:editother', $contact->getPermissionUser());
    }

    /**
     * @param array $ids
     *
     * @return Paginator
     */
    private function loadLeads($ids)
    {
        return $this->contactModel->getEntities([
            'filter' => [
                'force' => [
                    [
                        'column' => 'l.id',
                        'expr'   => 'in',
                        'value'  => $ids,
                    ],
                ],
            ],
        ]);
    }
}
