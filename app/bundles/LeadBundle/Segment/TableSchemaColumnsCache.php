<?php

namespace Mautic\LeadBundle\Segment;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Doctrine\Schema\ColumnIntrospector;

class TableSchemaColumnsCache
{
    private array $cache = [];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return array|false
     */
    public function getColumns($tableName)
    {
        if (!isset($this->cache[$tableName])) {
            $columns                 = ColumnIntrospector::listColumns($this->entityManager->getConnection()->createSchemaManager(), $tableName);
            $this->cache[$tableName] = $columns;
        }

        return $this->cache[$tableName];
    }

    public function clear(): static
    {
        $this->cache = [];

        return $this;
    }

    public function getCurrentDatabaseName(): ?string
    {
        return $this->entityManager->getConnection()->getDatabase();
    }
}
