<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\EventCollector;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\AbstractEventAccessor;
use Mautic\CampaignBundle\EventCollector\Accessor\EventAccessor;
use Mautic\CampaignBundle\EventCollector\Builder\ConnectionBuilder;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Translation\TranslatorInterface;

class EventCollector
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var array
     */
    private $eventsArray = [];

    /**
     * @var EventAccessor
     */
    private $events;

    /**
     * EventCollector constructor.
     *
     * @param TranslatorInterface      $translator
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(TranslatorInterface $translator, EventDispatcherInterface $dispatcher)
    {
        $this->translator = $translator;
        $this->dispatcher = $dispatcher;
    }

    /**
     * @return EventAccessor
     */
    public function getEvents()
    {
        if (empty($this->eventsArray)) {
            $this->buildEventList();
        }

        if (empty($this->events)) {
            $this->events = new EventAccessor($this->eventsArray);
        }

        return $this->events;
    }

    /**
     * @param string $type
     * @param string $key
     *
     * @return AbstractEventAccessor
     */
    public function getEventConfig(Event $event)
    {
        return $this->getEvents()->getEvent($event->getEventType(), $event->getType());
    }

    /**
     * Deprecated support for pre 2.13.
     *
     * @deprecated 2.13.0 to be removed in 3.0
     *
     * @param null|string $type
     *
     * @return array|mixed
     */
    public function getEventsArray($type = null)
    {
        if (empty($this->eventsArray)) {
            $this->buildEventList();
        }

        if (null !== $type) {
            if (!isset($this->events[$type])) {
                throw new \InvalidArgumentException("$type not found as array key");
            }

            return $this->eventsArray[$type];
        }

        return $this->eventsArray;
    }

    private function buildEventList()
    {
        //build them
        $event  = new CampaignBuilderEvent($this->translator);
        $this->dispatcher->dispatch(CampaignEvents::CAMPAIGN_ON_BUILD, $event);

        $this->eventsArray[Event::TYPE_ACTION]    = $event->getActions();
        $this->eventsArray[Event::TYPE_CONDITION] = $event->getConditions();
        $this->eventsArray[Event::TYPE_DECISION]  = $event->getDecisions();

        $this->eventsArray['connectionRestrictions'] = ConnectionBuilder::buildRestrictionsArray($this->eventsArray);
    }
}
