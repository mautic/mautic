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

        // Build a single atomic upsert that inserts "1" into the total column for the
        // current (form_id, hour) bucket, or — when a row for that bucket already
        // exists — increments the existing total by 1 instead of overwriting it.
        //
        // $updateSet            ['total' => 'total + :new_total']
        //                       Custom SET expression: "total = total + <new value>".
        //                       A plain replacement ('total' => null or ['total'])
        //                       would overwrite the counter, so the increment form
        //                       must be used here.
        //
        // $newValuePlaceholders [':new_total' => 'total']
        //                       Replaces the ":new_total" token with the platform
        //                       specific reference to the new row's "total" value.
        //                       The ":name" token format matches DBAL named parameters,
        //                       so it cannot accidentally match ":new_total_foo", etc.
        //
        // $innerSql             'VALUES (:formId, :hour, 1)'
        //                       Direct VALUES source → produces a single-statement
        //                       INSERT...ON CONFLICT / INSERT...ON DUPLICATE KEY.
        //                       (A "SELECT ..." source would take the summarize
        //                       subquery branch instead.)
        //
        // Generated SQL — one statement, atomic on both platforms:
        //
        //   PostgreSQL:
        //     INSERT INTO {prefix}form_submission_totals (form_id, hour, total)
        //     VALUES (:formId, :hour, 1)
        //     ON CONFLICT (form_id, hour)
        //     DO UPDATE SET total = total + EXCLUDED.total
        //
        //   MariaDB / MySQL:
        //     INSERT INTO {prefix}form_submission_totals (form_id, hour, total)
        //     VALUES (:formId, :hour, 1)
        //     ON DUPLICATE KEY UPDATE total = total + VALUES(total)
        //
        // Semantics are identical: insert 1 on first hit, increment by 1 on conflict.
        $sql = DatabasePlatform::getComplexUpsertStatement(
            $connection->getDatabasePlatform(),
            MAUTIC_TABLE_PREFIX.SubmissionTotal::TABLE_NAME,
            'form_id, hour, total',
            'VALUES (:formId, :hour, 1)',
            'form_id, hour',
            ['total' => 'total + :new_total'],
            [':new_total' => 'total'],
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
