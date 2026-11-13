<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Entity;

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

        $this->getEntityManager()->getConnection()->executeStatement(
            'INSERT INTO '.MAUTIC_TABLE_PREFIX.SubmissionTotal::TABLE_NAME.' (form_id, hour, total) VALUES (:formId, :hour, 1)'
            .' ON DUPLICATE KEY UPDATE total = total + 1',
            ['formId' => $formId, 'hour' => $hour->format('Y-m-d H:i:s')]
        );
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
