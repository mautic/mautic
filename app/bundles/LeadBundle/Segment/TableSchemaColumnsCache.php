<?php

namespace Mautic\LeadBundle\Segment;

use Doctrine\ORM\EntityManager;

/**
 * Class TableSchemaColumnsCache.
 */
class TableSchemaColumnsCache
{
    /**
     * @var array
     */
    private $cache;

    /**
     * TableSchemaColumnsCache constructor.
     */
    public function __construct(private EntityManager $entityManager)
    {
        $this->cache         = [];
    }

    public function getColumns($tableName): array|false
    {
        if (!isset($this->cache[$tableName])) {
            $columns                 = $this->entityManager->getConnection()->getSchemaManager()->listTableColumns($tableName);
            $this->cache[$tableName] = $columns ?: [];
        }

        return $this->cache[$tableName];
    }

    /**
     * @return $this
     */
    public function clear()
    {
        $this->cache = [];

        return $this;
    }

    /**
     * @return string
     */
    public function getCurrentDatabaseName()
    {
        return $this->entityManager->getConnection()->getDatabase();
    }
}
