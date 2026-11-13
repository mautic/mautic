<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Helper;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\TrackingPreference;
use Mautic\LeadBundle\Entity\TrackingPreferenceRepository;

/**
 * Reads and stores whether a contact lets Mautic record their activity on their profile.
 */
final readonly class ContactTrackingPreferenceHelper
{
    public function __construct(
        private TrackingPreferenceRepository $trackingPreferenceRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Only a contact who turned it off is left out, so a contact who hasn't chosen keeps the current behavior.
     */
    public function allowsActivityTracking(Lead $contact): bool
    {
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

        $this->entityManager->persist($preference);
        $this->entityManager->flush();
    }
}
