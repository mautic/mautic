<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\EventListener;

use Mautic\LeadBundle\Entity\TrackingPreference;
use Mautic\LeadBundle\Event\LeadMergeEvent;
use Mautic\LeadBundle\Event\LeadPostMergeEvent;
use Mautic\LeadBundle\Helper\ContactTrackingPreferenceHelper;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final readonly class TrackingPreferenceSubscriber
{
    public function __construct(
        private ContactTrackingPreferenceHelper $contactTrackingPreferenceHelper,
    ) {
    }

    /**
     * The merged contact keeps a choice to turn tracking off from either contact, before the other one is deleted
     * along with its choices.
     */
    #[AsEventListener(event: LeadPostMergeEvent::class)]
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
