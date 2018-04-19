<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Model;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;

class SegmentsActionModel
{
    /**
     * @var int[]
     */
    private $leadsIds;

    /**
     * @var LeadModel
     */
    private $leadModel;

    /**
     * @param LeadModel       $leadModel
     * @param CorePermissions $permissions
     */
    public function __construct(LeadModel $leadModel, CorePermissions $permissions)
    {
        $this->leadModel   = $leadModel;
        $this->permissions = $permissions;
    }

    /**
     * @param array $contactIds
     * @param array $segmentIds
     */
    public function addContacts(array $contactIds, array $segmentIds)
    {
        $contacts = $this->getContacts($contactIds);

        foreach ($contacts as $contact) {
            if (!$this->canEdit($contact)) {
                continue;
            }

            $this->leadModel->addToLists($contact, $segmentIds);
        }

        $this->leadModel->saveEntities($contacts);
    }

    /**
     * @param array $contactIds
     * @param array $segmentIds
     */
    public function remmoveContacts(array $contactIds, array $segmentIds)
    {
        $contacts = $this->getContacts($contactIds);

        foreach ($contacts as $contact) {
            if (!$this->canEdit($contact)) {
                continue;
            }

            $this->leadModel->removeFromLists($contact, $segmentIds);
        }

        $this->leadModel->saveEntities($contacts);
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
    private function getContacts(array $ids)
    {
        return $this->leadModel->getEntities([
            'filter' => [
                'force' => [
                    [
                        'column' => 'l.id',
                        'expr'   => 'in',
                        'value'  => $this->leadsIds,
                    ],
                ],
            ],
        ]);
    }
}
