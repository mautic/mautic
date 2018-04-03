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

use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\LeadBundle\Batches\Lead\ChangeCategoriesAction\ChangeCategoriesActionFactoryInterface;
use Mautic\LeadBundle\Model\LeadModel;

final class ChangeSegmentsActionFactory implements ChangeCategoriesActionFactoryInterface
{
    /**
     * @var LeadModel
     */
    private $leadModel;

    /**
     * @var CorePermissions
     */
    private $corePermissions;

    /**
     * ChangeCategoriesActionFactory constructor.
     *
     * @param LeadModel       $leadModel
     * @param CorePermissions $corePermissions
     */
    public function __construct(LeadModel $leadModel, CorePermissions $corePermissions)
    {
        $this->leadModel       = $leadModel;
        $this->corePermissions = $corePermissions;
    }

    /**
     * @see ChangeCategoriesActionFactoryInterface::create()
     * {@inheritdoc}
     */
    public function create(array $leadsIds, array $segmentsIdsToAdd, array $segmentsIdsToRemove)
    {
        return new ChangeSegmentsAction($this->leadModel, $this->corePermissions, $leadsIds, $segmentsIdsToAdd, $segmentsIdsToRemove);
    }
}
