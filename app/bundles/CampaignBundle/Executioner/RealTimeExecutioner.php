<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Executioner;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\EventRepository;
use Mautic\CampaignBundle\Entity\LeadRepository;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\DecisionAccessor;
use Mautic\CampaignBundle\EventCollector\EventCollector;
use Mautic\CampaignBundle\Executioner\Event\DecisionExecutioner as Executioner;
use Mautic\CampaignBundle\Executioner\Exception\CampaignNotExecutableException;
use Mautic\CampaignBundle\Executioner\Exception\DecisionNotApplicableException;
use Mautic\CampaignBundle\Executioner\Result\Responses;
use Mautic\CampaignBundle\Executioner\Scheduler\EventScheduler;
use Mautic\CampaignBundle\Helper\ChannelExtractor;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Psr\Log\LoggerInterface;

class RealTimeExecutioner
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var LeadModel
     */
    private $leadModel;

    /**
     * @var Lead
     */
    private $contact;

    /**
     * @var array
     */
    private $events;

    /**
     * @var EventRepository
     */
    private $eventRepository;

    /**
     * @var EventExecutioner
     */
    private $executioner;

    /**
     * @var Executioner
     */
    private $decisionExecutioner;

    /**
     * @var EventCollector
     */
    private $collector;

    /**
     * @var EventScheduler
     */
    private $scheduler;

    /**
     * @var ContactTracker
     */
    private $contactTracker;

    /**
     * @var Responses
     */
    private $responses;

    /**
     * @var LeadRepository
     */
    private $leadRepository;

    /**
     * RealTimeExecutioner constructor.
     *
     * @param LoggerInterface  $logger
     * @param LeadModel        $leadModel
     * @param EventRepository  $eventRepository
     * @param EventExecutioner $executioner
     * @param Executioner      $decisionExecutioner
     * @param EventCollector   $collector
     * @param EventScheduler   $scheduler
     * @param ContactTracker   $contactTracker
     * @param LeadRepository   $leadRepository
     */
    public function __construct(
        LoggerInterface $logger,
        LeadModel $leadModel,
        EventRepository $eventRepository,
        EventExecutioner $executioner,
        Executioner $decisionExecutioner,
        EventCollector $collector,
        EventScheduler $scheduler,
        ContactTracker $contactTracker,
        LeadRepository $leadRepository
    ) {
        $this->logger              = $logger;
        $this->leadModel           = $leadModel;
        $this->eventRepository     = $eventRepository;
        $this->executioner         = $executioner;
        $this->decisionExecutioner = $decisionExecutioner;
        $this->collector           = $collector;
        $this->scheduler           = $scheduler;
        $this->contactTracker      = $contactTracker;
        $this->leadRepository      = $leadRepository;
    }

    /**
     * @param string      $type
     * @param mixed       $passthrough
     * @param string|null $channel
     * @param int|null    $channelId
     *
     * @return Responses
     *
     * @throws Dispatcher\Exception\LogNotProcessedException
     * @throws Dispatcher\Exception\LogPassedAndFailedException
     * @throws Exception\CannotProcessEventException
     * @throws Scheduler\Exception\NotSchedulableException
     */
    public function execute($type, $passthrough = null, $channel = null, $channelId = null)
    {
        $this->responses = new Responses();
        $now             = new \DateTime();

        $this->logger->debug('CAMPAIGN: Campaign triggered for event type '.$type.'('.$channel.' / '.$channelId.')');

        // Kept for BC support although not sure we need this
        defined('MAUTIC_CAMPAIGN_NOT_SYSTEM_TRIGGERED') or define('MAUTIC_CAMPAIGN_NOT_SYSTEM_TRIGGERED', 1);

        try {
            $this->fetchCurrentContact();
        } catch (CampaignNotExecutableException $exception) {
            $this->logger->debug('CAMPAIGN: '.$exception->getMessage());

            return $this->responses;
        }

        try {
            $this->fetchCampaignData($type);
        } catch (CampaignNotExecutableException $exception) {
            $this->logger->debug('CAMPAIGN: '.$exception->getMessage());

            return $this->responses;
        }

        /** @var Event $event */
        foreach ($this->events as $event) {
            try {
                $this->evaluateDecisionForContact($event, $passthrough, $channel, $channelId);
            } catch (DecisionNotApplicableException $exception) {
                $this->logger->debug('CAMPAIGN: Event ID '.$event->getId().' is not applicable ('.$exception->getMessage().')');

                continue;
            }

            $children = $event->getPositiveChildren();
            if (!$children->count()) {
                $this->logger->debug('CAMPAIGN: Event ID '.$event->getId().' has no positive children');

                continue;
            }

            $this->executeAssociatedEvents($children, $now);
        }

        // Save any changes to the contact done by the listeners
        if ($this->contact->getChanges()) {
            $this->leadModel->saveEntity($this->contact, false);
        }

        return $this->responses;
    }

    /**
     * @param ArrayCollection $children
     * @param \DateTime       $now
     *
     * @throws Dispatcher\Exception\LogNotProcessedException
     * @throws Dispatcher\Exception\LogPassedAndFailedException
     * @throws Exception\CannotProcessEventException
     * @throws Scheduler\Exception\NotSchedulableException
     */
    private function executeAssociatedEvents(ArrayCollection $children, \DateTime $now)
    {
        $children = clone $children;

        /** @var Event $child */
        foreach ($children as $key => $child) {
            $executionDate = $this->scheduler->getExecutionDateTime($child, $now);
            $this->logger->debug(
                'CAMPAIGN: Event ID# '.$child->getId().
                ' to be executed on '.$executionDate->format('Y-m-d H:i:s')
            );

            if ($this->scheduler->shouldSchedule($executionDate, $now)) {
                $this->scheduler->scheduleForContact($child, $executionDate, $this->contact);

                $children->remove($key);
            }
        }

        if ($children->count()) {
            $this->executioner->executeEventsForContact($children, $this->contact, $this->responses);
        }
    }

    /**
     * @param Event       $event
     * @param mixed       $passthrough
     * @param string|null $channel
     * @param int|null    $channelId
     *
     * @throws DecisionNotApplicableException
     * @throws Exception\CannotProcessEventException
     */
    private function evaluateDecisionForContact(Event $event, $passthrough = null, $channel = null, $channelId = null)
    {
        $this->logger->debug('CAMPAIGN: Executing '.$event->getType().' ID '.$event->getId().' for contact ID '.$this->contact->getId());

        if ($event->getEventType() !== Event::TYPE_DECISION) {
            @trigger_error(
                "{$event->getType()} is not assigned to a decision and no longer supported. ".
                'Check that you are executing RealTimeExecutioner::execute for an event registered as a decision.',
                E_USER_DEPRECATED
            );

            throw new DecisionNotApplicableException("Event {$event->getId()} is not a decision.");
        }

        // If channels do not match up at all (not even fuzzy logic i.e. page vs page.redirect), there's no need to go further
        if ($channel && $event->getChannel() && strpos($channel, $event->getChannel()) === false) {
            throw new DecisionNotApplicableException("Channels, $channel and {$event->getChannel()}, do not match.");
        }

        if ($channel && $channelId && $event->getChannelId() && $channelId !== $event->getChannelId()) {
            throw new DecisionNotApplicableException("Channel IDs, $channelId and {$event->getChannelId()}, do not match for $channel.");
        }

        // Check if parent taken path is the path of this event, otherwise exit
        $parentEvent = $event->getParent();
        if ($parentEvent !== null && $event->getDecisionPath() !== null) {
            $rotation    = $this->leadRepository->getContactRotations([$this->contact->getId()], $event->getCampaign()->getId());
            $log         = $parentEvent->getLogByContactAndRotation($this->contact, $rotation);

            if ($log === null) {
                throw new DecisionNotApplicableException("Parent {$parentEvent->getId()} has not been fired, event {$event->getId()} should not be fired.");
            }

            $pathTaken   = (int) $log->getNonActionPathTaken();

            if ($pathTaken === 1 && !$parentEvent->getNegativeChildren()->contains($event)) {
                throw new DecisionNotApplicableException("Parent {$parentEvent->getId()} take negative path, event {$event->getId()} is on positive path.");
            } elseif ($pathTaken === 0 && !$parentEvent->getPositiveChildren()->contains($event)) {
                throw new DecisionNotApplicableException("Parent {$parentEvent->getId()} take positive path, event {$event->getId()} is on negative path.");
            }
        }

        /** @var DecisionAccessor $config */
        $config = $this->collector->getEventConfig($event);
        $this->decisionExecutioner->evaluateForContact($config, $event, $this->contact, $passthrough, $channel, $channelId);
    }

    /**
     * @throws CampaignNotExecutableException
     */
    private function fetchCurrentContact()
    {
        $this->contact = $this->contactTracker->getContact();
        if (!$this->contact instanceof Lead || !$this->contact->getId()) {
            throw new CampaignNotExecutableException('Unidentifiable contact');
        }

        $this->logger->debug('CAMPAIGN: Current contact ID# '.$this->contact->getId());
    }

    /**
     * @param $type
     *
     * @throws CampaignNotExecutableException
     */
    private function fetchCampaignData($type)
    {
        if (!$this->events = $this->eventRepository->getContactPendingEvents($this->contact->getId(), $type)) {
            throw new CampaignNotExecutableException('Contact does not have any applicable '.$type.' associations.');
        }

        // 2.14 BC break workaround - pre 2.14 had a bug that recorded channelId for decisions as 1 regardless of actually ID
        // if channelIdField was an array and only one item was selected. That caused the channel ID check in evaluateDecisionForContact
        // to fail resulting in the decision never being evaluated. Therefore we are going to self heal these decisions.
        /** @var Event $event */
        foreach ($this->events as $event) {
            if (1 === $event->getChannelId()) {
                ChannelExtractor::setChannel($event, $event, $this->collector->getEventConfig($event));

                $this->eventRepository->saveEntity($event);
            }
        }

        $this->logger->debug('CAMPAIGN: Found '.count($this->events).' events to analyze for contact ID '.$this->contact->getId());
    }
}
