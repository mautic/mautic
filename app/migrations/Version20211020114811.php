<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\DatabasePlatform;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20211020114811 extends PreUpAssertionMigration
{
    private const COMPANIES_TABLE           = 'companies';
    private const SYNC_OBJECT_MAPPING_TABLE = 'sync_object_mapping';

    private const INDEX_COMPANY_MATCH         = 'company_match';
    private const INDEX_INTEGRATION_OBJECT    = 'integration_object';
    private const INDEX_INTEGRATION_REFERENCE = 'integration_reference';

    protected function preUpAssertions(): void
    {
        // Skip if any of the problematic indexes are missing (i.e. already cleaned)
        $this->skipAssertion(
            fn () => !$this->indexExists(self::COMPANIES_TABLE, self::INDEX_COMPANY_MATCH),
            sprintf('The index %s does not exist in the %s table.', self::INDEX_COMPANY_MATCH, $this->getPrefixedTableName(self::COMPANIES_TABLE))
        );

        $this->skipAssertion(
            fn () => !$this->indexExists(self::SYNC_OBJECT_MAPPING_TABLE, self::INDEX_INTEGRATION_OBJECT),
            sprintf('The index %s does not exist in the %s table.', self::INDEX_INTEGRATION_OBJECT, $this->getPrefixedTableName(self::SYNC_OBJECT_MAPPING_TABLE))
        );

        $this->skipAssertion(
            fn () => !$this->indexExists(self::SYNC_OBJECT_MAPPING_TABLE, self::INDEX_INTEGRATION_REFERENCE),
            sprintf('The index %s does not exist in the %s table.', self::INDEX_INTEGRATION_REFERENCE, $this->getPrefixedTableName(self::SYNC_OBJECT_MAPPING_TABLE))
        );

        $this->skipAssertion(
            fn () => empty($this->getTablesToConvert()),
            'No tables require character set/collation conversion.'
        );
    }

    public function up(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();

        // PostgreSQL does not have FOREIGN_KEY_CHECKS – skip that part
        if (!DatabasePlatform::isPostgreSQL($platform)) {
            $this->addSql('SET FOREIGN_KEY_CHECKS=0;');
        }

        // 1. Companies table – shorten indexed columns to 191 chars
        $companiesTable = $this->getPrefixedTableName(self::COMPANIES_TABLE);

        $this->dropIndexIfExists($companiesTable, self::INDEX_COMPANY_MATCH);
        $this->createIndex(
            $companiesTable,
            self::INDEX_COMPANY_MATCH,
            ['companyname(191)', 'companycity(191)', 'companycountry(191)', 'companystate(191)']
        );

        // 2. sync_object_mapping table – shorten indexed columns to 191 chars
        $syncTable = $this->getPrefixedTableName(self::SYNC_OBJECT_MAPPING_TABLE);

        $this->dropIndexIfExists($syncTable, self::INDEX_INTEGRATION_OBJECT);
        $this->createIndex(
            $syncTable,
            self::INDEX_INTEGRATION_OBJECT,
            ['integration(191)', 'integration_object_name(191)', 'integration_object_id(191)', 'integration_reference_id(191)']
        );

        $this->dropIndexIfExists($syncTable, self::INDEX_INTEGRATION_REFERENCE);
        $this->createIndex(
            $syncTable,
            self::INDEX_INTEGRATION_REFERENCE,
            ['integration(191)', 'integration_object_name(191)', 'integration_reference_id(191)', 'integration_object_id(191)']
        );

        // 3. Convert tables to utf8mb4 / UTF8
        $tables = $this->getTablesToConvert();
        foreach ($tables as $table) {
            $tableName = $table['TABLE_NAME'];

            if (DatabasePlatform::isPostgreSQL($platform)) {
                // PostgreSQL: change collation (no charset concept)
                $this->addSql(sprintf(
                    'ALTER TABLE %s ALTER COLUMN companyname TYPE varchar(191) COLLATE "utf8_general_ci",
                     ALTER COLUMN companycity TYPE varchar(191) COLLATE "utf8_general_ci",
                     ALTER COLUMN companycountry TYPE varchar(191) COLLATE "utf8_general_ci",
                     ALTER COLUMN companystate TYPE varchar(191) COLLATE "utf8_general_ci"',
                    $tableName
                ));
            } else {
                // MySQL
                $this->addSql(sprintf(
                    'ALTER TABLE %s CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;',
                    $tableName
                ));
            }
        }

        if (!DatabasePlatform::isPostgreSQL($platform)) {
            $this->addSql('SET FOREIGN_KEY_CHECKS=1;');
        }
    }

    private function dropIndexIfExists(string $tableName, string $indexName): void
    {
        if ($this->indexExists($tableName, $indexName)) {
            $this->dropIndex(
                $tableName,
                $indexName
            );
        }
    }

    /**
     * Returns tables that still need conversion (not utf8mb4_unicode_ci)
     * On PostgreSQL we return an empty array or implement different logic if needed.
     *
     * @return array<mixed>
     */
    private function getTablesToConvert(): array
    {
        $platform = $this->connection->getDatabasePlatform();

        if (DatabasePlatform::isPostgreSQL($platform)) {
            // On PostgreSQL we usually don't need to change charset (already UTF8)
            // If you want to enforce specific collation, implement check here
            return [];
        }

        $sql = "
            SELECT TABLE_NAME
            FROM information_schema.TABLES T
            INNER JOIN information_schema.COLLATION_CHARACTER_SET_APPLICABILITY C
                ON C.collation_name = T.table_collation
            WHERE T.TABLE_SCHEMA = ?
              AND (C.CHARACTER_SET_NAME != 'utf8mb4' OR C.COLLATION_NAME != 'utf8mb4_unicode_ci')
        ";

        $stmt = $this->connection->executeQuery($sql, [$this->connection->getDatabase()]);

        return $stmt->fetchAllAssociative();
    }
}
