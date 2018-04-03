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

use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\LeadBundle\Model\LeadModel;

final class ChangeCategoriesActionFactory implements ChangeCategoriesActionFactoryInterface
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
    public function create(array $leadsIds, array $categoriesIdsToAdd, array $categoriesIdsToRemove)
    {
        return new ChangeCategoriesAction($this->leadModel, $this->corePermissions, $leadsIds, $categoriesIdsToAdd, $categoriesIdsToRemove);
    }
}
