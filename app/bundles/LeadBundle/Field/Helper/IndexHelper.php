<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Field\Helper;

use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use Mautic\LeadBundle\Entity\Lead;

/**
 * Helper for getting and counting indexes on lead table.
 *
 * @see Lead
 */
class IndexHelper
{
    const MAX_COUNT_ALLOWED = 64;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var bool|array
     */
    private $indexedColumns = false;

    /**
     * Can be different from indexed column count when using multiple indexes on same table.
     *
     * @var int
     */
    private $indexCount = 0;

    /**
     * * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @return array|bool
     */
    public function getIndexedColumnNames()
    {
        $this->getIndexes();

        return $this->indexedColumns;
    }

    /**
     * @return int
     */
    public function getIndexCount()
    {
        $this->getIndexes();

        return $this->indexCount;
    }

    /**
     * @return int
     */
    public function getMaxCount()
    {
        return self::MAX_COUNT_ALLOWED;
    }

    /**
     * @return bool
     */
    public function isNewIndexAllowed()
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
    private function getIndexes()
    {
        if ($this->indexedColumns !== false) {
            // Query below performed
            return;
        }

        $tableName = $this->entityManager->getClassMetadata(Lead::class)->getTableName();

        $sql = "SHOW INDEXES FROM `$tableName`";

        $stmt = $this->entityManager->getConnection()->prepare($sql);
        $stmt->execute();
        $indexes = $stmt->fetchAll(); // Can be empty array, but it's not possible in Mautic

        $this->indexedColumns = array_map(
            function ($index) {
                return $index['Column_name'];
            },
            $indexes
        );

        $this->indexCount = count($indexes);
    }
}
