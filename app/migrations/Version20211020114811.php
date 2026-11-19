<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20211020114811 extends PreUpAssertionMigration
{
    private const COMPANIES_TABLE           = 'companies';
    private const SYNC_OBJECT_MAPPING_TABLE = 'sync_object_mapping';

    private const INDEX_COMPANY_MATCH         = MAUTIC_TABLE_PREFIX.'company_match';
    private const INDEX_INTEGRATION_OBJECT    = MAUTIC_TABLE_PREFIX.'integration_object';
    private const INDEX_INTEGRATION_REFERENCE = MAUTIC_TABLE_PREFIX.'integration_reference';

    protected function preUpAssertions(): void
    {
        $this->skipAssertion(
            fn (Schema $schema) => !$schema->getTable($this->getPrefixedTableName(self::COMPANIES_TABLE))->hasIndex(self::INDEX_COMPANY_MATCH),
            sprintf('The index %s does not exist in the %s table.', self::INDEX_COMPANY_MATCH, $this->getPrefixedTableName(self::COMPANIES_TABLE))
        );

        $this->skipAssertion(
            fn (Schema $schema) => !$schema->getTable($this->getPrefixedTableName(self::SYNC_OBJECT_MAPPING_TABLE))->hasIndex(self::INDEX_INTEGRATION_OBJECT),
            sprintf('The index %s does not exist in the %s table.', self::INDEX_INTEGRATION_OBJECT, $this->getPrefixedTableName(self::SYNC_OBJECT_MAPPING_TABLE))
        );

        $this->skipAssertion(
            fn (Schema $schema) => !$schema->getTable($this->getPrefixedTableName(self::SYNC_OBJECT_MAPPING_TABLE))->hasIndex(self::INDEX_INTEGRATION_REFERENCE),
            sprintf('The index %s does not exist in the %s table.', self::INDEX_INTEGRATION_REFERENCE, $this->getPrefixedTableName(self::SYNC_OBJECT_MAPPING_TABLE))
        );

        $this->skipAssertion(
            fn () => empty($this->getTables()),
            'No tables require character set conversion.'
        );
    }

    public function up(Schema $schema): void
    {
        $this->addSql('SET FOREIGN_KEY_CHECKS=0;');

        /*
         * The index key prefix length limit is 3072 bytes for InnoDB tables.
         * In utf8mb4, 1 char uses 4 bytes
         * So if we are creating multiple columns index for 4 columns of varchar(255) then it will take 255*4*4 = 4080.
         * 4080 bytes is more than max allowed limit 3072 bytes.
         * So it was not allowing to convert charset for particular tables and was showing error
         * "Specified key was too long; max key length is 3072 bytes"
         */

        $dropIndexQuery = 'DROP INDEX %s ON %s';

        $this->addSql(
            sprintf(
                $dropIndexQuery,
                self::INDEX_COMPANY_MATCH,
                $this->getPrefixedTableName(self::COMPANIES_TABLE)
            )
        );
        $this->addSql(
            sprintf(
                'CREATE INDEX %s ON %s(`companyname`(191),`companycity`(191),`companycountry`(191),`companystate`(191))',
                self::INDEX_COMPANY_MATCH,
                $this->getPrefixedTableName(self::COMPANIES_TABLE)
            )
        );

        $this->addSql(
            sprintf(
                $dropIndexQuery,
                self::INDEX_INTEGRATION_OBJECT,
                $this->getPrefixedTableName(self::SYNC_OBJECT_MAPPING_TABLE)
            )
        );
        $this->addSql(
            sprintf(
                'CREATE INDEX %s ON %s(`integration`(191),`integration_object_name`(191),`integration_object_id`(191), `integration_reference_id`(191))',
                self::INDEX_INTEGRATION_OBJECT,
                $this->getPrefixedTableName(self::SYNC_OBJECT_MAPPING_TABLE)
            )
        );

        $this->addSql(
            sprintf(
                $dropIndexQuery,
                self::INDEX_INTEGRATION_REFERENCE,
                $this->getPrefixedTableName(self::SYNC_OBJECT_MAPPING_TABLE)
            )
        );
        $this->addSql(
            sprintf(
                'CREATE INDEX %s ON %s(`integration`(191),`integration_object_name`(191), `integration_reference_id`(191), `integration_object_id`(191))',
                self::INDEX_INTEGRATION_REFERENCE,
                $this->getPrefixedTableName(self::SYNC_OBJECT_MAPPING_TABLE)
            )
        );

        $tables = $this->getTables();
        foreach ($tables as $table) {
            $tableName = $table['TABLE_NAME'];
            $this->addSql(
                sprintf(
                    'ALTER TABLE %s CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;',
                    $tableName)
            );
        }
        $this->addSql('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * @return mixed[]
     *
     * @throws Exception
     */
    private function getTables(): array
    {
        $stmt = $this->connection->executeQuery(
            "SELECT TABLE_NAME, TABLE_COLLATION FROM information_schema.TABLES AS T
            INNER JOIN information_schema.COLLATION_CHARACTER_SET_APPLICABILITY AS C
            ON (C.collation_name = T.table_collation) WHERE
            T.TABLE_SCHEMA = '{$this->connection->getDatabase()}' AND
            (C.CHARACTER_SET_NAME != 'utf8mb4' OR C.COLLATION_NAME != 'utf8mb4_unicode_ci')"
        );

        return $stmt->fetchAllAssociative();
    }
}
