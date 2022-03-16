<?php

namespace Mautic\CampaignBundle\Event;

use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\AbstractEventAccessor;

/**
 * Trait EventArrayTrait.
 *
 * @deprecated 2.13.0; used for BC support. To be removed in 3.0
 */
trait EventArrayTrait
{
    /**
     * @var array
     */
    protected $eventArray = [];

    /**
     * Used to convert entities to the old array format; tried to minimize the need for this except where needed.
     *
     * @return array
     */
    protected function getEventArray(Event $event)
    {
        $eventId = $event->getId();
        if (isset($this->eventArray[$eventId])) {
            return $this->eventArray[$eventId];
        }

        $eventArray = $event->convertToArray();
        $campaign   = $event->getCampaign();

        $eventArray['campaign'] = [
            'id'        => $campaign->getId(),
            'name'      => $campaign->getName(),
            'createdBy' => $campaign->getCreatedBy(),
        ];

        $eventArray['parent'] = null;
        if ($parent = $event->getParent()) {
            $eventArray['parent']             = $parent->convertToArray();
            $eventArray['parent']['campaign'] = $eventArray['campaign'];
        }

        $eventArray['children'] = [];
        if ($children = $event->getChildren()) {
            /** @var Event $child */
            foreach ($children as $child) {
                $childArray             = $child->convertToArray();
                $childArray['parent']   =&$eventArray;
                $childArray['campaign'] =&$eventArray['campaign'];
                unset($childArray['children']);

                $eventArray['children'] = $childArray;
            }
        }

        $this->eventArray[$eventId] = $eventArray;

        return $this->eventArray[$eventId];
    }

    /**
     * @return array
     */
    protected function getLegacyEventsArray(LeadEventLog $log)
    {
        $event = $log->getEvent();

        return [
            $event->getCampaign()->getId() => [
                $this->getEventArray($event),
            ],
        ];
    }

    /**
     * @return array
     */
    protected function getLegacyEventsConfigArray(Event $event, AbstractEventAccessor $config)
    {
        return [
            $event->getEventType() => [
                $event->getType() => [
                    $config->getConfig(),
                ],
            ],
        ];
    }
}
