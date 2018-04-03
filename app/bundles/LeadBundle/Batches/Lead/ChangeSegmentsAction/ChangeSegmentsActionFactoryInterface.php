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

interface ChangeSegmentsActionFactoryInterface
{
    /**
     * Create action.
     *
     * @param int[] $leadsIds
     * @param int[] $segmentsIdsToAdd
     * @param int[] $segmentsIdsToRemove
     *
     * @return ActionInterface
     */
    public function create(array $leadsIds, array $segmentsIdsToAdd, array $segmentsIdsToRemove);
}
