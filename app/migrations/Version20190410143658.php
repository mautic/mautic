<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20190410143658 extends AbstractMauticMigration
{
    protected const TABLE_NAME = 'lead_donotcontact';
    protected const INDEX_NAME = 'leadid_reason_channel';

    public function preUp(Schema $schema): void
    {
        $tableName    = $this->getPrefixedTableName(self::TABLE_NAME);
        $newIndexName = $this->getPrefixedIndexName(self::INDEX_NAME);

        // Check real existence via SQL (case-insensitive where needed)
        if ($this->indexExists($tableName, $newIndexName)) {
            throw new SkipMigration('The composite index already exists - skipping');
        }
    }

    public function up(Schema $schema): void
    {
        $tableName    = $this->getPrefixedTableName(self::TABLE_NAME);
        $newIndexName = $this->getPrefixedIndexName(self::INDEX_NAME);

        // Skip creation if already exists (extra safety)
        if ($this->indexExists($tableName, $newIndexName)) {
            return;
        }

        $this->createIndex(
            $tableName,
            $newIndexName,
            ['lead_id', 'channel', 'reason']
        );

        // Drop any old single-column lead_id indexes (find dynamically)
        $oldIndexes = $this->findSingleLeadIdIndexes($tableName);

        foreach ($oldIndexes as $oldName) {
            $this->dropIndex(
                $tableName,
                $oldName,
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
        $indexes = $this->getIndexes($tableName);

        $toDrop = [];
        foreach ($indexes as $index) {
            $columns = $index->getColumns();
            if (1 === count($columns) && 'lead_id' === $columns[0]) {
                $toDrop[] = $index->getName();
            }
        }

        return $toDrop;
    }
}
