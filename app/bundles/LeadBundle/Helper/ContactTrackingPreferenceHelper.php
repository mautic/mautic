<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Helper;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\TrackingPreference;

/**
 * Reads and stores whether a contact lets Mautic record their activity on their profile.
 */
readonly class ContactTrackingPreferenceHelper
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Only a contact who turned it off is left out, so a contact who hasn't chosen keeps the current behavior.
     */
    public function allowsActivityTracking(Lead $contact): bool
    {
        $preference = $this->findPreference($contact, TrackingPreference::PURPOSE_ACTIVITY);

        return null === $preference || $preference->isAllowed();
    }

    public function setActivityTracking(Lead $contact, bool $allowed, ?string $source = null): void
    {
        $preference = $this->findPreference($contact, TrackingPreference::PURPOSE_ACTIVITY);

        if (null === $preference) {
            $preference = new TrackingPreference($contact, TrackingPreference::PURPOSE_ACTIVITY, $allowed, $source);
        } else {
            $preference->change($allowed, $source);
        }

        $this->entityManager->persist($preference);
        $this->entityManager->flush();
    }

    private function findPreference(Lead $contact, string $purpose): ?TrackingPreference
    {
        $preference = $this->entityManager->createQueryBuilder()
            ->select('p')
            ->from(TrackingPreference::class, 'p')
            ->where('p.lead = :contact')
            ->andWhere('p.purpose = :purpose')
            ->setParameter('contact', $contact)
            ->setParameter('purpose', $purpose)
            ->getQuery()
            ->getOneOrNullResult();

        return $preference instanceof TrackingPreference ? $preference : null;
    }
}
