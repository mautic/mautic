<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Field\Helper;

use Doctrine\DBAL\Exception as DBALException;
use Doctrine\ORM\EntityManager;
use Mautic\LeadBundle\Entity\Lead;

/**
 * Helper for getting and counting indexes on lead table.
 *
 * @see Lead
 */
class IndexHelper
{
    public const MAX_COUNT_ALLOWED = 64;
    /**
     * @var bool|array<string>
     */
    private $indexedColumns = false;

    /**
     * Can be different from indexed column count when using multiple indexes on same table.
     */
    private int $indexCount = 0;

    public function __construct(private EntityManager $entityManager)
    {
    }

    /**
     * @return array<string>|bool
     */
    public function getIndexedColumnNames()
    {
        $this->getIndexes();

        return $this->indexedColumns;
    }

    public function getIndexCount(): int
    {
        $this->getIndexes();

        return $this->indexCount;
    }

    public function getMaxCount(): int
    {
        return self::MAX_COUNT_ALLOWED;
    }

    public function isNewIndexAllowed(): bool
    {
        return $this->getIndexCount() < $this->getMaxCount();
    }

    /**
     * Get indexes created on `leads` table.
     *
     * @see Lead
     *
     * @throws DBALException
     */
    private function getIndexes(): void
    {
        if (false !== $this->indexedColumns) {
            // Query below performed
            return;
        }

        $tableName = $this->entityManager->getClassMetadata(Lead::class)->getTableName();

        $sql = "SHOW INDEXES FROM `$tableName`";

        $stmt    = $this->entityManager->getConnection()->prepare($sql);
        $indexes = $stmt->executeQuery()->fetchAllAssociative();

        $this->indexedColumns = array_map(
            fn ($index) => $index['Column_name'],
            $indexes
        );

        $this->indexCount = count($indexes);
    }
}
