<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Entity;

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * @extends CommonRepository<SegmentCompany>
 */
class SegmentCompanyRepository extends CommonRepository
{
    /**
     * @param array<int, int> $segmentIds
     *
     * @return array<int, int>
     */
    public function getCompanyCount(array $segmentIds): array
    {
        $q = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $q->select('COUNT(1) as thecount, '.SegmentCompany::RELATIONS_NAME.'.segment_id')
            ->from($this->getPreTable().SegmentCompany::TABLE_NAME, SegmentCompany::RELATIONS_NAME)
            ->groupBy(SegmentCompany::RELATIONS_NAME.'.segment_id');

        if (1 === count($segmentIds)) {
            $q          = $this->forceUseIndex($q, $this->getPreTable().'companies_segment_manually_removed');
            $expression = $q->expr()->eq(SegmentCompany::RELATIONS_NAME.'.segment_id', (string) $segmentIds[0]);
        } else {
            $expression = $q->expr()->in(SegmentCompany::RELATIONS_NAME.'.segment_id', array_map(static fn ($segmentId): string => (string) $segmentId, $segmentIds));
        }

        $q->where($expression);
        $q->andWhere(SegmentCompany::RELATIONS_NAME.'.manually_removed = :false')
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
     * @return SegmentCompany[]
     */
    public function getSegmentCompaniesBySegmentIds(array $segmentIds): array
    {
        $result = $this->findBy([
            'companySegment'  => $segmentIds,
            'manuallyRemoved' => 0,
        ]);

        return array_values(array_unique($result, SORT_REGULAR));
    }

    /**
     * Check if contact's primary company is in any company segment.
     */
    public function isContactPrimaryCompanyInAnySegment(int $contactId): bool
    {
        $tableName           = MAUTIC_TABLE_PREFIX.SegmentCompany::TABLE_NAME;
        $companiesLeadsTable = MAUTIC_TABLE_PREFIX.'companies_leads';

        $sql = <<<SQL
            SELECT cs.segment_id
            FROM $tableName cs
            INNER JOIN $companiesLeadsTable cl ON cs.company_id = cl.company_id
            WHERE cl.lead_id = ?
              AND cl.is_primary = 1
              AND cs.manually_removed = 0
            LIMIT 1
            SQL;

        $segmentIds = $this->getEntityManager()->getConnection()
            ->executeQuery(
                $sql,
                [$contactId],
                [\PDO::PARAM_INT]
            )
            ->fetchFirstColumn();

        return !empty($segmentIds);
    }

    public function isNotContactPrimaryCompanyInAnySegment(int $contactId): bool
    {
        return !$this->isContactPrimaryCompanyInAnySegment($contactId);
    }

    /**
     * @param int[] $expectedSegmentIds
     */
    public function isContactPrimaryCompanyInSegments(int $contactId, array $expectedSegmentIds): bool
    {
        $segmentIds = $this->fetchContactPrimaryCompanyToSegmentIdsRelationships($contactId, $expectedSegmentIds);

        return !empty($segmentIds);
    }

    /**
     * @param int[] $expectedSegmentIds
     */
    public function isNotContactPrimaryCompanyInSegments(int $contactId, array $expectedSegmentIds): bool
    {
        $segmentIds = $this->fetchContactPrimaryCompanyToSegmentIdsRelationships($contactId, $expectedSegmentIds);

        if (empty($segmentIds)) {
            return true; // Contact's primary company is not in any segment
        }

        foreach ($expectedSegmentIds as $expectedSegmentId) {
            if (in_array($expectedSegmentId, $segmentIds)) { // No exact type comparison used!
                return false;
            }
        }

        return true;
    }

    /**
     * @param int[] $expectedSegmentIds
     */
    public function isContactPrimaryCompanyInAllSegments(int $contactId, array $expectedSegmentIds): bool
    {
        $segmentIds = $this->fetchContactPrimaryCompanyToSegmentIdsRelationships($contactId, $expectedSegmentIds);

        return count($segmentIds) === count($expectedSegmentIds);
    }

    /**
     * @param int[] $expectedSegmentIds
     */
    public function isNotContactPrimaryCompanyInAllSegments(int $contactId, array $expectedSegmentIds): bool
    {
        $segmentIds = $this->fetchContactPrimaryCompanyToSegmentIdsRelationships($contactId, $expectedSegmentIds);

        return [] === $segmentIds;
    }

    /**
     * @param int[] $expectedSegmentIds
     *
     * @return int[]
     */
    private function fetchContactPrimaryCompanyToSegmentIdsRelationships(int $contactId, array $expectedSegmentIds): array
    {
        $tableName           = MAUTIC_TABLE_PREFIX.SegmentCompany::TABLE_NAME;
        $companiesLeadsTable = MAUTIC_TABLE_PREFIX.'companies_leads';

        $sql = <<<SQL
            SELECT cs.segment_id
            FROM $tableName cs
            INNER JOIN $companiesLeadsTable cl ON cs.company_id = cl.company_id
            WHERE cl.lead_id = ?
              AND cl.is_primary = 1
              AND cs.segment_id IN (?)
              AND cs.manually_removed = 0
            SQL;

        return $this->getEntityManager()->getConnection()
            ->executeQuery(
                $sql,
                [$contactId, $expectedSegmentIds],
                [
                    \PDO::PARAM_INT,
                    \Doctrine\DBAL\ArrayParameterType::INTEGER,
                ]
            )
            ->fetchFirstColumn();
    }

    private function getPreTable(): string
    {
        if (is_string(MAUTIC_TABLE_PREFIX)) {
            return MAUTIC_TABLE_PREFIX;
        }

        return '';
    }
}
