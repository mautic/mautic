<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;
use Mautic\CoreBundle\Doctrine\DatabasePlatform;

class Version20190410143658 extends AbstractMauticMigration
{
    public function preUp(Schema $schema): void
    {
        $tableName    = $this->getTableName();
        $newIndexName = $this->getNewIndexName();

        // Check real existence via SQL (case-insensitive where needed)
        if ($this->indexExists($tableName, $newIndexName)) {
            throw new SkipMigration('The composite index already exists - skipping');
        }
    }

    public function up(Schema $schema): void
    {
        $platform     = $this->connection->getDatabasePlatform();
        $tableName    = $this->getTableName();
        $newIndexName = $this->getNewIndexName();

        // Skip creation if already exists (extra safety)
        if ($this->indexExists($tableName, $newIndexName)) {
            return;
        }

        $this->addSql(
            DatabasePlatform::getCreateIndexSql(
                $platform,
                $tableName,
                $newIndexName,
                ['lead_id', 'channel', 'reason']
            )
        );

        // Drop any old single-column lead_id indexes (find dynamically)
        $oldIndexes = $this->findSingleLeadIdIndexes($tableName);

        foreach ($oldIndexes as $oldName) {
            $this->addSql(
                DatabasePlatform::getDropIndexSql(
                    $platform,
                    $tableName,
                    $oldName,
                    false,
                    true
                )
            );
        }
    }

    /**
     * Find any single-column indexes on lead_id (to safely drop old ones).
     *
     * @return array<string>
     */
    private function findSingleLeadIdIndexes(string $tableName): array
    {
        $indexes = DatabasePlatform::listTableIndexes(
            $this->connection,
            $tableName
        );

        $toDrop = [];
        foreach ($indexes as $index) {
            $columns = $index->getColumns();
            if (1 === count($columns) && 'lead_id' === $columns[0]) {
                $toDrop[] = $index->getName();
            }
        }

        return $toDrop;
    }

    private function getNewIndexName(): string
    {
        return "{$this->prefix}leadid_reason_channel";
    }

    private function getTableName(): string
    {
        return "{$this->prefix}lead_donotcontact";
    }
}
