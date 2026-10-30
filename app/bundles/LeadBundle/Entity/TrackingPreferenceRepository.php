<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * @extends CommonRepository<TrackingPreference>
 */
final class TrackingPreferenceRepository extends CommonRepository
{
    public function findOneByContactAndPurpose(Lead $contact, string $purpose): ?TrackingPreference
    {
        return $this->findOneBy(['lead' => $contact, 'purpose' => $purpose]);
    }
}
