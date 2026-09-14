<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Field;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Index;

final class EmailLookupIndex
{
    public function __construct(
        private Connection $connection,
        private ?string $tablePrefix,
    ) {
    }

    public function ensure(): void
    {
        $tablePrefix   = $this->tablePrefix ?? '';
        $tableName     = $tablePrefix.'leads';
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist([$tableName])) {
            return;
        }

        foreach ($schemaManager->introspectTable($tableName)->getIndexes() as $index) {
            $columns = $index->getColumns();
            if (isset($columns[0]) && 'email' === $columns[0]) {
                return;
            }
        }

        $index = new Index($tablePrefix.'email_search', ['email']);
        $this->connection->executeStatement(
            $this->connection->getDatabasePlatform()->getCreateIndexSQL($index, $tableName),
        );
    }
}
