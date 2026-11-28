<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Entity;

use Mautic\CoreBundle\Doctrine\DatabasePlatform;
use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * @extends CommonRepository<SubmissionTotal>
 */
final class SubmissionTotalRepository extends CommonRepository
{
    /**
     * Counts one more submission in the hour it was made. The totals are written with one statement so that two
     * submissions in the same hour never race for the row.
     */
    public function increment(int $formId, \DateTimeInterface $submittedAt): void
    {
        $hour = \DateTimeImmutable::createFromInterface($submittedAt)->setTimezone(new \DateTimeZone('UTC'));
        $hour = $hour->setTime((int) $hour->format('H'), 0);

        $connection = $this->getEntityManager()->getConnection();
        $metadata   = $this->getEntityManager()->getClassMetadata(SubmissionTotal::class);

        // Get ID handling for PostgreSQL (nextval) vs MySQL (auto_increment)
        $idValues = DatabasePlatform::getInsertIdValues(
            $connection->getDatabasePlatform(),
            MAUTIC_TABLE_PREFIX.SubmissionTotal::TABLE_NAME,
            $metadata
        );

        $columns = [
            'form_id', 'hour', 'total',
        ];

        $values = [
            ':formId', ':hour', '1',
        ];

        // Add id column if exists
        if ([] !== $idValues) {
            array_unshift($columns, ...array_keys($idValues));
            array_unshift($values, ...array_values($idValues));
        }

        $sql = DatabasePlatform::getComplexUpsertStatement(
            $connection->getDatabasePlatform(),
            MAUTIC_TABLE_PREFIX.SubmissionTotal::TABLE_NAME,
            implode(', ', $columns),
            'VALUES ('.implode(', ', $values).')',
            'form_id, hour',
            ['total' => ':old_total + 1'],   // increment pattern: existing + 1
            [],                              // no new-value placeholders needed
            [':old_total' => 'total'],       // existing-value placeholder, caller-chosen token
        );

        $connection->executeStatement($sql, [
            'formId' => $formId,
            'hour'   => $hour->format('Y-m-d H:i:s'),
        ]);
    }

    public function getTotal(int $formId): int
    {
        $query = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $query->select('COALESCE(SUM(t.total), 0)')
            ->from(MAUTIC_TABLE_PREFIX.SubmissionTotal::TABLE_NAME, 't')
            ->where('t.form_id = :formId')
            ->setParameter('formId', $formId);

        return (int) $query->executeQuery()->fetchOne();
    }

    public function getTableAlias(): string
    {
        return 't';
    }
}
