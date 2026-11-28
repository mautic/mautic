<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\EventListener;

use Mautic\LeadBundle\Entity\TrackingPreference;
use Mautic\LeadBundle\Event\LeadMergeEvent;
use Mautic\LeadBundle\Event\TrackingPreferenceChangeEvent;
use Mautic\LeadBundle\Helper\ContactTrackingPreferenceHelper;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Tracker\TrackingIdentifierRevoker;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class TrackingPreferenceSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ContactTrackingPreferenceHelper $contactTrackingPreferenceHelper,
        private TrackingIdentifierRevoker $trackingIdentifierRevoker,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // After the other contact's devices were moved to the merged contact
            LeadEvents::LEAD_POST_MERGE                                => ['onLeadPostMerge', -10],
            TrackingPreferenceChangeEvent::TRACKING_PREFERENCE_CHANGE => ['onTrackingPreferenceChange', 0],
        ];
    }

    /**
     * The merged contact keeps a choice to turn tracking off from either contact, before the other one is deleted
     * along with its choices. The devices it took over are no longer recognised either.
     */
    public function onLeadPostMerge(LeadMergeEvent $event): void
    {
        $winner = $event->getVictor();
        $loser  = $event->getLoser();

        if (!$this->contactTrackingPreferenceHelper->allowsActivityTracking($loser)
            && $this->contactTrackingPreferenceHelper->allowsActivityTracking($winner)) {
            $this->contactTrackingPreferenceHelper->setActivityTracking($winner, false, TrackingPreference::SOURCE_CONTACT_MERGE);

            return;
        }

        if (!$this->contactTrackingPreferenceHelper->allowsActivityTracking($winner)) {
            $this->trackingIdentifierRevoker->revokeContact($winner);
        }
    }

    /**
     * A contact who turns tracking off isn't recognised again through the cookies or devices tracked until now.
     */
    public function onTrackingPreferenceChange(TrackingPreferenceChangeEvent $event): void
    {
        if (TrackingPreference::PURPOSE_ACTIVITY !== $event->getPurpose() || $event->isAllowed()) {
            return;
        }

        $this->trackingIdentifierRevoker->revokeContact($event->getContact());
    }
}
