<?php

namespace Mautic\CampaignBundle\EventCollector\Builder;

use Mautic\CampaignBundle\EventCollector\Accessor\Event\ActionAccessor;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\ConditionAccessor;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\DecisionAccessor;

class EventBuilder
{
    public static function buildActions(array $actions): array
    {
        $converted = [];
        foreach ($actions as $key => $actionArray) {
            $converted[$key] = new ActionAccessor($actionArray);
        }

        return $converted;
    }

    public static function buildConditions(array $conditions): array
    {
        $converted = [];
        foreach ($conditions as $key => $conditionArray) {
            $converted[$key] = new ConditionAccessor($conditionArray);
        }

        return $converted;
    }

    public static function buildDecisions(array $decisions): array
    {
        $converted = [];
        foreach ($decisions as $key => $decisionArray) {
            $converted[$key] = new DecisionAccessor($decisionArray);
        }

        return $converted;
    }
}
