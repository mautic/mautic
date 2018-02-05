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

use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\EventRepository;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\DecisionAccessor;
use Mautic\CampaignBundle\EventCollector\EventCollector;
use Mautic\CampaignBundle\Executioner\Event\Decision;
use Mautic\CampaignBundle\Executioner\Exception\CampaignNotExecutableException;
use Mautic\CampaignBundle\Executioner\Exception\DecisionNotApplicableException;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use Psr\Log\LoggerInterface;

class DecisionExecutioner
{
    //if (Event::PATH_INACTION === $event->getDecisionPath() && Event::TYPE_CONDITION !== $event->getType() && $inactionPathProhibted) {

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
     * @var Decision
     */
    private $decisionExecutioner;

    /**
     * @var EventCollector
     */
    private $collector;

    /**
     * DecisionExecutioner constructor.
     *
     * @param LoggerInterface  $logger
     * @param LeadModel        $leadModel
     * @param EventRepository  $eventRepository
     * @param EventExecutioner $executioner
     * @param Decision         $decisionExecutioner
     * @param EventCollector   $collector
     */
    public function __construct(
        LoggerInterface $logger,
        LeadModel $leadModel,
        EventRepository $eventRepository,
        EventExecutioner $executioner,
        Decision $decisionExecutioner,
        EventCollector $collector
    ) {
        $this->logger              = $logger;
        $this->leadModel           = $leadModel;
        $this->eventRepository     = $eventRepository;
        $this->executioner         = $executioner;
        $this->decisionExecutioner = $decisionExecutioner;
        $this->collector           = $collector;
    }

    /**
     * @param      $type
     * @param null $passthrough
     * @param null $channel
     * @param null $channelId
     *
     * @throws Dispatcher\Exception\LogNotProcessedException
     * @throws Dispatcher\Exception\LogPassedAndFailedException
     */
    public function execute($type, $passthrough = null, $channel = null, $channelId = null)
    {
        $this->logger->debug('CAMPAIGN: Campaign triggered for event type '.$type.'('.$channel.' / '.$channelId.')');

        // Kept for BC support although not sure we need this
        defined('MAUTIC_CAMPAIGN_NOT_SYSTEM_TRIGGERED') or define('MAUTIC_CAMPAIGN_NOT_SYSTEM_TRIGGERED', 1);

        try {
            $this->fetchCurrentContact();
        } catch (CampaignNotExecutableException $exception) {
            $this->logger->debug('CAMPAIGN: '.$exception->getMessage());

            return;
        }

        try {
            $this->fetchCampaignData($type);
        } catch (CampaignNotExecutableException $exception) {
            $this->logger->debug('CAMPAIGN: '.$exception->getMessage());

            return;
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

            foreach ($children as $child) {
                $this->executioner->executeForContact($child, $this->contact);
            }
        }
    }

    /**
     * @param Event $event
     * @param null  $passthrough
     * @param null  $channel
     * @param null  $channelId
     *
     * @throws DecisionNotApplicableException
     * @throws Exception\CannotProcessEventException
     */
    private function evaluateDecisionForContact(Event $event, $passthrough = null, $channel = null, $channelId = null)
    {
        $this->logger->debug('CAMPAIGN: Executing '.$event->getType().' ID '.$event->getId().' for contact ID '.$this->contact->getId());

        // If channels do not match up, there's no need to go further
        if ($channel && $event->getChannel() && $channel !== $event->getChannel()) {
            throw new DecisionNotApplicableException('channels do not match');
        }

        if ($channel && $channelId && $event->getChannelId() && $channelId !== $event->getChannelId()) {
            throw new DecisionNotApplicableException('channel IDs do not match for channel '.$channel);
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
        $this->contact = $this->leadModel->getCurrentLead();
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

        $this->logger->debug('CAMPAIGN: Found '.count($this->events).' events to analyize for contact ID '.$this->contact->getId());
    }
}
