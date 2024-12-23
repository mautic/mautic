<?php

namespace Mautic\LeadBundle\Segment;

use Doctrine\ORM\EntityManager;

class TableSchemaColumnsCache
{
    private array $cache;

    public function __construct(
        private EntityManager $entityManager,
    ) {
        $this->cache         = [];
    }

    /**
     * @return array|false
     */
    public function getColumns($tableName)
    {
        if (!isset($this->cache[$tableName])) {
            $columns                 = $this->entityManager->getConnection()->createSchemaManager()->listTableColumns($tableName);
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
