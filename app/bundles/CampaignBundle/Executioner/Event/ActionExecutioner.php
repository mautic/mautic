<?php

namespace Mautic\CampaignBundle\Executioner\Event;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\AbstractEventAccessor;
use Mautic\CampaignBundle\Executioner\Dispatcher\ActionDispatcher;
use Mautic\CampaignBundle\Executioner\Exception\CannotProcessEventException;
use Mautic\CampaignBundle\Executioner\Logger\EventLogger;
use Mautic\CampaignBundle\Executioner\Result\EvaluatedContacts;

class ActionExecutioner implements EventInterface
{
    const TYPE = 'action';

    /**
     * @var ActionDispatcher
     */
    private $dispatcher;

    /**
     * @var EventLogger
     */
    private $eventLogger;

    /**
     * ActionExecutioner constructor.
     */
    public function __construct(ActionDispatcher $dispatcher, EventLogger $eventLogger)
    {
        $this->dispatcher         = $dispatcher;
        $this->eventLogger        = $eventLogger;
    }

    /**
     * @return EvaluatedContacts
     *
     * @throws CannotProcessEventException
     * @throws \Mautic\CampaignBundle\Executioner\Dispatcher\Exception\LogNotProcessedException
     * @throws \Mautic\CampaignBundle\Executioner\Dispatcher\Exception\LogPassedAndFailedException
     */
    public function execute(AbstractEventAccessor $config, ArrayCollection $logs)
    {
        /** @var LeadEventLog $firstLog */
        if (!$firstLog = $logs->first()) {
            return new EvaluatedContacts();
        }

        $event = $firstLog->getEvent();

        if (Event::TYPE_ACTION !== $event->getEventType()) {
            throw new CannotProcessEventException('Cannot process event ID '.$event->getId().' as an action.');
        }

        // Execute to process the batch of contacts
        $pendingEvent = $this->dispatcher->dispatchEvent($config, $event, $logs);

        /** @var ArrayCollection $contacts */
        $passed = $this->eventLogger->extractContactsFromLogs($pendingEvent->getSuccessful());
        $failed = $this->eventLogger->extractContactsFromLogs($pendingEvent->getFailures());

        return new EvaluatedContacts($passed, $failed);
    }
}
