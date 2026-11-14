<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Field\Helper;

use Doctrine\DBAL\Exception as DBALException;
use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Doctrine\DatabasePlatform;
use Mautic\CoreBundle\Doctrine\Helper\IndexSchemaHelper;
use Mautic\LeadBundle\Entity\Lead;

/**
 * Helper for getting and counting indexes on lead table.
 *
 * @see Lead
 */
class IndexHelper
{
    /**
     * @var bool|array<string>
     */
    private $indexedColumns = false;

    /**
     * Can be different from indexed column count when using multiple indexes on same table.
     */
    private int $indexCount = 0;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly IndexSchemaHelper $indexSchemaHelper,
    ) {
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
        return DatabasePlatform::getMaxIndexAllowed($this->entityManager->getConnection()->getDatabasePlatform());
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

        // Use get table index implementation which work well with PostgreSQL too
        // This bypasses the buggy PostgreSQL Doctrine introspection in older DBAL versions (below 4.0)
        $indexes = $this->indexSchemaHelper->getTableIndexes($tableName);

        $indexedColumns = [];

        foreach ($indexes as $index) {
            $columns = $index->getColumns();

            foreach ($columns as $column) {
                $indexedColumns[] = $column;
            }
        }

        $this->indexedColumns = $indexedColumns;
        // index column count may not be equal indexed column count
        // (unique search index may include more than 1 column)
        $this->indexCount     = count($indexes);
    }
}
