<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\EventCollector\Accessor;

use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\AbstractEventAccessor;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\ActionAccessor;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\DecisionAccessor;
use Mautic\CampaignBundle\EventCollector\Accessor\Exception\EventNotFoundException;
use Mautic\CampaignBundle\EventCollector\Accessor\Exception\TypeNotFoundException;
use Mautic\CampaignBundle\EventCollector\Builder\EventBuilder;

class EventAccessor
{
    /**
     * @var array
     */
    private $actions = [];

    /**
     * @var array
     */
    private $conditions = [];

    /**
     * @var array
     */
    private $decisions = [];

    /**
     * EventAccessor constructor.
     *
     * @param array $events
     */
    public function __construct(array $events)
    {
        $this->buildEvents($events);
    }

    /**
     * @param string $type
     * @param string $key
     *
     * @return AbstractEventAccessor
     *
     * @throws TypeNotFoundException
     * @throws EventNotFoundException
     */
    public function getEvent($type, $key)
    {
        switch ($type) {
            case Event::TYPE_ACTION:
                return $this->getAction($key);
            case Event::TYPE_CONDITION:
                return $this->getCondition($key);
            case Event::TYPE_DECISION:
                return $this->getDecision($key);
            default:
                throw new TypeNotFoundException("$type is not a valid event type");
        }
    }

    /**
     * @param string $key
     *
     * @return ActionAccessor
     *
     * @throws EventNotFoundException
     */
    public function getAction($key)
    {
        if (!isset($this->actions[$key])) {
            throw new EventNotFoundException("Action $key is not valid");
        }

        return $this->actions[$key];
    }

    /**
     * @return array
     */
    public function getActions()
    {
        return $this->actions;
    }

    /**
     * @param string $key
     *
     * @return mixed
     *
     * @throws EventNotFoundException
     */
    public function getCondition($key)
    {
        if (!isset($this->conditions[$key])) {
            throw new EventNotFoundException("Condition $key is not valid");
        }

        return $this->conditions[$key];
    }

    /**
     * @return array
     */
    public function getConditions()
    {
        return $this->conditions;
    }

    /**
     * @param string $key
     *
     * @return DecisionAccessor
     *
     * @throws EventNotFoundException
     */
    public function getDecision($key)
    {
        if (!isset($this->decisions[$key])) {
            throw new EventNotFoundException("Decision $key is not valid");
        }

        return $this->decisions[$key];
    }

    /**
     * @return array
     */
    public function getDecisions()
    {
        return $this->decisions;
    }

    /**
     * @param array $events
     */
    private function buildEvents(array $events)
    {
        if (isset($events[Event::TYPE_ACTION])) {
            $this->actions = EventBuilder::buildActions($events[Event::TYPE_ACTION]);
        }

        if (isset($events[Event::TYPE_CONDITION])) {
            $this->conditions = EventBuilder::buildConditions($events[Event::TYPE_CONDITION]);
        }

        if (isset($events[Event::TYPE_DECISION])) {
            $this->decisions = EventBuilder::buildDecisions($events[Event::TYPE_DECISION]);
        }
    }
}
