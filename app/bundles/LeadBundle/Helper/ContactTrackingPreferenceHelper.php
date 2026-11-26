<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Helper;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\TrackingPreference;
use Mautic\LeadBundle\Entity\TrackingPreferenceRepository;
use Mautic\LeadBundle\Event\TrackingPreferenceChangeEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Reads and stores whether a contact lets Mautic record their activity on their profile.
 */
final readonly class ContactTrackingPreferenceHelper
{
    public function __construct(
        private TrackingPreferenceRepository $trackingPreferenceRepository,
        private EntityManagerInterface $entityManager,
        private EventDispatcherInterface $dispatcher,
    ) {
    }

    /**
     * Only a contact who turned it off is left out, so a contact who hasn't chosen keeps the current behavior.
     */
    public function allowsActivityTracking(Lead $contact): bool
    {
        // A contact that isn't saved yet has no stored choice to look up
        if (!$contact->getId()) {
            return true;
        }

        $preference = $this->trackingPreferenceRepository->findOneByContactAndPurpose($contact, TrackingPreference::PURPOSE_ACTIVITY);

        return null === $preference || $preference->isAllowed();
    }

    public function setActivityTracking(Lead $contact, bool $allowed, ?string $source = null): void
    {
        $preference = $this->trackingPreferenceRepository->findOneByContactAndPurpose($contact, TrackingPreference::PURPOSE_ACTIVITY);

        if (null === $preference) {
            $preference = new TrackingPreference($contact, TrackingPreference::PURPOSE_ACTIVITY, $allowed, $source);
        } else {
            $preference->change($allowed, $source);
        }

        // One transaction, so a choice is never stored without what its listeners do, like forgetting the tracking identifiers
        $this->entityManager->wrapInTransaction(function () use ($preference, $contact, $allowed, $source): void {
            $this->entityManager->persist($preference);
            $this->entityManager->flush();

            $this->dispatcher->dispatch(
                new TrackingPreferenceChangeEvent($contact, TrackingPreference::PURPOSE_ACTIVITY, $allowed, $source),
                TrackingPreferenceChangeEvent::TRACKING_PREFERENCE_CHANGE
            );
        });
    }
}
