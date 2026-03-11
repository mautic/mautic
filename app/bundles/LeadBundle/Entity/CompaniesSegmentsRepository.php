<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Entity;

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * @extends CommonRepository<CompaniesSegments>
 *
 * @see ListLeadRepository
 */
class CompaniesSegmentsRepository extends CommonRepository
{
    /**
     * @param array<int, int> $segmentIds
     *
     * @return array<int, int>
     */
    public function getCompanyCount(array $segmentIds): array
    {
        $q = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $q->select('COUNT(1) as thecount, '.CompaniesSegments::RELATIONS_NAME.'.segment_id')
            ->from($this->getPreTable().CompaniesSegments::TABLE_NAME, CompaniesSegments::RELATIONS_NAME)
            ->groupBy(CompaniesSegments::RELATIONS_NAME.'.segment_id');

        if (1 === count($segmentIds)) {
            $q          = $this->forceUseIndex($q, $this->getPreTable().'companies_segment_manually_removed');
            $expression = $q->expr()->eq(CompaniesSegments::RELATIONS_NAME.'.segment_id', (string) array_pop($segmentIds));
        } else {
            $expression = $q->expr()->in(CompaniesSegments::RELATIONS_NAME.'.segment_id', array_map(static function ($segmentId): string {
                return (string) $segmentId;
            }, $segmentIds));
        }

        $q->where($expression);
        $q->andWhere(CompaniesSegments::RELATIONS_NAME.'.manually_removed = :false')
            ->setParameter('false', false, ParameterType::BOOLEAN);

        $result = $q->executeQuery()->fetchAllAssociative();

        $return = [];
        foreach ($result as $r) {
            \assert(is_numeric($r['segment_id']));
            \assert(is_numeric($r['thecount']));
            $return[(int) $r['segment_id']] = (int) $r['thecount'];
        }

        foreach ($segmentIds as $l) {
            if (!isset($return[$l])) {
                $return[$l] = 0;
            }
        }

        return $return;
    }

    private function forceUseIndex(QueryBuilder $qb, string $indexName): QueryBuilder
    {
        $fromPart = $qb->getQueryPart('from');
        \assert(is_array($fromPart));
        if (
            !array_key_exists(0, $fromPart)
            || !is_array($fromPart[0])
            || !array_key_exists('alias', $fromPart[0])
            || !array_key_exists('table', $fromPart[0])
            || !is_string($fromPart[0]['alias'])
            || !is_string($fromPart[0]['table'])
        ) {
            return $qb;
        }

        $fromPart[0]['alias'] = sprintf('%s USE INDEX (%s)', $fromPart[0]['alias'], $indexName);
        $qb->resetQueryPart('from');
        $qb->from($fromPart[0]['table'], $fromPart[0]['alias']);

        return $qb;
    }

    /**
     * @param int[] $segmentIds
     *
     * @return CompaniesSegments[]
     */
    public function getCompaniesSegmentsBySegmentIds(array $segmentIds): array
    {
        $result = $this->findBy([
            'companySegment'  => $segmentIds,
            'manuallyRemoved' => 0,
        ]);

        return array_values(array_unique($result, SORT_REGULAR));
    }

    private function getPreTable(): string
    {
        if (is_string(MAUTIC_TABLE_PREFIX)) {
            return MAUTIC_TABLE_PREFIX;
        }

        return '';
    }
}
