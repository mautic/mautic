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

interface ChangeCategoriesActionFactoryInterface
{
    /**
     * Create action.
     *
     * @param int[] $leadsIds
     * @param int[] $categoriesIdsToAdd
     * @param int[] $categoriesIdsToRemove
     *
     * @return ActionInterface
     */
    public function create(array $leadsIds, array $categoriesIdsToAdd, array $categoriesIdsToRemove);
}
