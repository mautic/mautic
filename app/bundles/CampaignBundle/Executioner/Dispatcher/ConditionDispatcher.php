<?php

namespace Mautic\CampaignBundle\Executioner\Dispatcher;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Event\ConditionEvent;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\ConditionAccessor;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ConditionDispatcher
{
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * ConditionDispatcher constructor.
     */
    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * @return ConditionEvent
     */
    public function dispatchEvent(ConditionAccessor $config, LeadEventLog $log)
    {
        $event = new ConditionEvent($config, $log);
        $this->dispatcher->dispatch($config->getEventName(), $event);
        $this->dispatcher->dispatch(CampaignEvents::ON_EVENT_CONDITION_EVALUATION, $event);

        return $event;
    }
}
