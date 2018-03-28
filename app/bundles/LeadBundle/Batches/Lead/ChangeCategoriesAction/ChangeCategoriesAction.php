<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Batches\Lead\ChangeCategoriesAction;

use Mautic\CoreBundle\Batches\ActionInterface;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\LeadBundle\Model\LeadModel;

final class ChangeCategoriesAction implements ActionInterface
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
    private $categoriesIdsToAdd;

    /**
     * @var int[]
     */
    private $categoriesIdsToRemove;

    /**
     * ChangeCategoriesAction constructor.
     *
     * @param LeadModel       $leadModel
     * @param CorePermissions $corePermissions
     * @param int[]           $leadsIds
     * @param int[]           $categoriesIdsToAdd
     * @param int[]           $categoriesIdsToRemove
     */
    public function __construct(LeadModel $leadModel, CorePermissions $corePermissions, array $leadsIds, array $categoriesIdsToAdd, array $categoriesIdsToRemove)
    {
        $this->leadModel             = $leadModel;
        $this->corePermissions       = $corePermissions;
        $this->leadsIds              = $leadsIds;
        $this->categoriesIdsToAdd    = $categoriesIdsToAdd;
        $this->categoriesIdsToRemove = $categoriesIdsToRemove;
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
            $leadCategories = $this->leadModel->getLeadCategories($lead); // id of categories
            $this->leadModel->addToCategory($lead, $this->categoriesIdsToAdd);

            $deletedCategories = array_intersect($leadCategories, $this->categoriesIdsToRemove);
            if (!empty($deletedCategories)) {
                $this->leadModel->removeFromCategories($deletedCategories);
            }
        }
    }
}
