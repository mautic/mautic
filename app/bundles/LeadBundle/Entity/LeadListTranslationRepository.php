<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * @extends CommonRepository<LeadListTranslation>
 */
final class LeadListTranslationRepository extends CommonRepository
{
    /**
     * @param list<int> $segmentIds
     *
     * @return array<int, array<string, string>>
     */
    public function findForSegmentIds(array $segmentIds): array
    {
        if ([] === $segmentIds) {
            return [];
        }

        $rows = $this->createQueryBuilder('translation')
            ->select('IDENTITY(translation.leadList) AS segmentId, translation.locale, translation.publicName')
            ->where('IDENTITY(translation.leadList) IN (:segmentIds)')
            ->setParameter('segmentIds', $segmentIds)
            ->orderBy('segmentId')
            ->addOrderBy('translation.locale')
            ->getQuery()
            ->getArrayResult();

        $translations = [];
        foreach ($rows as $row) {
            $translations[(int) $row['segmentId']][$row['locale']] = $row['publicName'];
        }

        return $translations;
    }
}
