<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Batches\Lead\ChangeSegmentsAction;

use Mautic\CoreBundle\Batches\ActionInterface;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\LeadBundle\Model\LeadModel;

final class ChangeSegmentsAction implements ActionInterface
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
     * @var CorePermissions
     */
    private $corePermissions;

    /**
     * @var int[]
     */
    private $segmentsIdsToAdd;

    /**
     * @var int[]
     */
    private $segmentsIdsToRemove;

    /**
     * ChangeSegmentsAction constructor.
     *
     * @param LeadModel       $leadModel
     * @param CorePermissions $corePermissions
     * @param array           $leadsIds
     * @param array           $segmentsIdsToAdd
     * @param array           $segmentsIdsToRemove
     */
    public function __construct(LeadModel $leadModel, CorePermissions $corePermissions, array $leadsIds, array $segmentsIdsToAdd, array $segmentsIdsToRemove)
    {
        $this->leadModel           = $leadModel;
        $this->corePermissions     = $corePermissions;
        $this->leadsIds            = $leadsIds;
        $this->segmentsIdsToAdd    = $segmentsIdsToAdd;
        $this->segmentsIdsToRemove = $segmentsIdsToRemove;
    }

    /**
     * @see ActionInterface::execute()
     * {@inheritdoc}
     */
    public function execute()
    {
        $leads = $this->leadModel->getEntities([
            'filter' => [
                'force' => [
                    [
                        'column' => 'l.id',
                        'expr'   => 'in',
                        'value'  => $this->leadsIds,
                    ],
                ],
            ],
            'ignore_paginator' => true,
        ]);

        foreach ($leads as $lead) {
            if (!$this->corePermissions->hasEntityAccess('lead:leads:editown', 'lead:leads:editother', $lead->getPermissionUser())) {
                continue;
            }

            if (!empty($this->segmentsIdsToAdd)) {
                $this->leadModel->addToLists($lead, $this->segmentsIdsToAdd);
            }

            if (!empty($this->segmentsIdsToRemove)) {
                $this->leadModel->removeFromLists($lead, $this->segmentsIdsToRemove);
            }
        }

        $this->leadModel->saveEntities($leads);
    }
}
