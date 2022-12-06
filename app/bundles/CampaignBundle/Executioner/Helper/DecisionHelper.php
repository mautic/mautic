<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Executioner\Helper;

use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadRepository;
use Mautic\CampaignBundle\Executioner\Exception\DecisionNotApplicableException;
use Mautic\LeadBundle\Entity\Lead;

class DecisionHelper
{
    private LeadRepository $leadRepository;

    public function __construct(
        LeadRepository $leadRepository
    ) {
        $this->leadRepository = $leadRepository;
    }

    /**
     * @throws DecisionNotApplicableException
     */
    public function checkIsDecisionApplicableForContact(Event $event, Lead $contact, ?string $channel = null, ?int $channelId = null): void
    {
        if (Event::TYPE_DECISION !== $event->getEventType()) {
            @trigger_error(
                "{$event->getType()} is not assigned to a decision and no longer supported. ".
                'Check that you are executing RealTimeExecutioner::execute for an event registered as a decision.',
                E_USER_DEPRECATED
            );

            throw new DecisionNotApplicableException("Event {$event->getId()} is not a decision.");
        }

        // If channels do not match up at all (not even fuzzy logic i.e. page vs page.redirect), there's no need to go further
        if ($channel && $event->getChannel() && false === strpos($channel, $event->getChannel())) {
            throw new DecisionNotApplicableException("Channels, $channel and {$event->getChannel()}, do not match.");
        }

        if ($channel && $channelId && $event->getChannelId() && $channelId !== $event->getChannelId()) {
            throw new DecisionNotApplicableException("Channel IDs, $channelId and {$event->getChannelId()}, do not match for $channel.");
        }

        // Check if parent taken path is the path of this event, otherwise exit
        $parentEvent = $event->getParent();

        if (null !== $parentEvent && null !== $event->getDecisionPath()) {
            $rotation    = $this->leadRepository->getContactRotations([$contact->getId()], $event->getCampaign()->getId());
            $log         = $parentEvent->getLogByContactAndRotation($contact, $rotation);

            if (null === $log) {
                throw new DecisionNotApplicableException("Parent {$parentEvent->getId()} has not been fired, event {$event->getId()} should not be fired.");
            }

            $pathTaken   = (int) $log->getNonActionPathTaken();

            if (1 === $pathTaken && !$parentEvent->getNegativeChildren()->contains($event)) {
                throw new DecisionNotApplicableException("Parent {$parentEvent->getId()} take negative path, event {$event->getId()} is on positive path.");
            } elseif (0 === $pathTaken && !$parentEvent->getPositiveChildren()->contains($event)) {
                throw new DecisionNotApplicableException("Parent {$parentEvent->getId()} take positive path, event {$event->getId()} is on negative path.");
            }
        }
    }
}
