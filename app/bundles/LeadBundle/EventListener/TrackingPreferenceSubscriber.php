<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\EventListener;

use Mautic\LeadBundle\Entity\TrackingPreference;
use Mautic\LeadBundle\Event\LeadMergeEvent;
use Mautic\LeadBundle\Helper\ContactTrackingPreferenceHelper;
use Mautic\LeadBundle\LeadEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class TrackingPreferenceSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ContactTrackingPreferenceHelper $contactTrackingPreferenceHelper,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LeadEvents::LEAD_POST_MERGE => ['onLeadPostMerge', 0],
        ];
    }

    /**
     * The merged contact keeps a choice to turn tracking off from either contact, before the other one is deleted
     * along with its choices.
     */
    public function onLeadPostMerge(LeadMergeEvent $event): void
    {
        $winner = $event->getVictor();
        $loser  = $event->getLoser();

        if (!$this->contactTrackingPreferenceHelper->allowsActivityTracking($loser)
            && $this->contactTrackingPreferenceHelper->allowsActivityTracking($winner)) {
            $this->contactTrackingPreferenceHelper->setActivityTracking($winner, false, TrackingPreference::SOURCE_CONTACT_MERGE);
        }
    }
}
