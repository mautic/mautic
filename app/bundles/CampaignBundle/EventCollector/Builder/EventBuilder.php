<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\EventCollector\Builder;

use Mautic\CampaignBundle\EventCollector\Accessor\Event\ActionAccessor;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\ConditionAccessor;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\DecisionAccessor;

class EventBuilder
{
    /**
     * @param array $actions
     *
     * @return array
     */
    public static function buildActions(array $actions)
    {
        $converted = [];
        foreach ($actions as $key => $actionArray) {
            $converted[$key] = new ActionAccessor($actionArray);
        }

        return $converted;
    }

    /**
     * @param array $conditions
     *
     * @return array
     */
    public static function buildConditions(array $conditions)
    {
        $converted = [];
        foreach ($conditions as $key => $conditionArray) {
            $converted[$key] = new ConditionAccessor($conditionArray);
        }

        return $converted;
    }

    /**
     * @param array $decisions
     *
     * @return array
     */
    public static function buildDecisions(array $decisions)
    {
        $converted = [];
        foreach ($decisions as $key => $decisionArray) {
            $converted[$key] = new DecisionAccessor($decisionArray);
        }

        return $converted;
    }
}
