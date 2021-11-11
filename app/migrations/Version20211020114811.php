<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20211020114811 extends AbstractMauticMigration
{
    private const DATA_TYPES_FOR_CHANGE_CHARSET = ['longtext', 'varchar', 'char', 'tinytext'];

    public function up(Schema $schema): void
    {
        $this->addSql('SET FOREIGN_KEY_CHECKS=0;');

        /*
         * The index key prefix length limit is 3072 bytes for InnoDB tables.
         * In utf8mb4, 1 char uses 4 bytes
         * So if we are creating multiple columns index for 4 columns of varchar(255) then it will take 255*4*4 = 4080. 4080 bytes is more than max allowed limit 3072 bytes.
         * So it was not allowing to convert charset for particular tables and was showing error "Specified key was too long; max key length is 3072 bytes"
         */

        $this->addSql(sprintf('DROP INDEX %s on %s', $this->prefix.'company_match', $this->getPrefixedTableName('companies')));
        $this->addSql(sprintf('CREATE INDEX %s on %s(`companyname`(191),`companycity`(191),`companycountry`(191),`companystate`(191))', $this->prefix.'company_match', $this->getPrefixedTableName('companies')));

        $this->addSql(sprintf('DROP INDEX %s on %s', $this->prefix.'citrix_event_product_name', $this->getPrefixedTableName('plugin_citrix_events')));
        $this->addSql(sprintf('CREATE INDEX %s on %s(`product`(191),`email`(191),`event_type`,`event_name`(191))', $this->prefix.'citrix_event_product_name', $this->getPrefixedTableName('plugin_citrix_events')));

        $this->addSql(sprintf('DROP INDEX %s on %s', $this->prefix.'integration_object', $this->getPrefixedTableName('sync_object_mapping')));
        $this->addSql(sprintf('CREATE INDEX %s on %s(`integration`(191),`integration_object_name`(191),`integration_object_id`(191), `integration_reference_id`(191))', $this->prefix.'integration_object', $this->getPrefixedTableName('sync_object_mapping')));

        $this->addSql(sprintf('DROP INDEX %s on %s', $this->prefix.'integration_reference', $this->getPrefixedTableName('sync_object_mapping')));
        $this->addSql(sprintf('CREATE INDEX %s on %s(`integration`(191),`integration_object_name`(191), `integration_reference_id`(191), `integration_object_id`(191))', $this->prefix.'integration_reference', $this->getPrefixedTableName('sync_object_mapping')));

        $tables = $this->getTables();
        foreach ($tables as $table) {
            $tableName = $table['TABLE_NAME'];
            $this->addSql(sprintf('ALTER TABLE %s CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;', $tableName));
        }

        $columns = $this->getColumns();
        foreach ($columns as $column) {
            $columnName = $column['COLUMN_NAME'];
            $columnType = $column['COLUMN_TYPE'];
            $tableName  = $column['TABLE_NAME'];
            $isNullable = 'NO' == $column['IS_NULLABLE'] ? ' NOT NULL' : '';
            $default    = !is_null($column['COLUMN_DEFAULT']) ? sprintf(' DEFAULT "%s"', $column['COLUMN_DEFAULT']) : '';
            $this->addSql(sprintf('ALTER TABLE %s MODIFY %s %s CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci %s %s;', $tableName, $columnName, $columnType, $isNullable, $default));
        }
        $this->addSql('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * @return array<string, mixed>
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    private function getColumns(): array
    {
        $dataTypes = "'".implode("','", self::DATA_TYPES_FOR_CHANGE_CHARSET)."'";
        $stmt      = $this->connection->executeQuery(
            "SELECT COLUMN_NAME, COLUMN_TYPE, TABLE_NAME, IS_NULLABLE, COLUMN_DEFAULT  FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = '{$this->connection->getDatabase()}' AND 
                                               DATA_TYPE IN ({$dataTypes}) AND
                                               EXTRA != 'VIRTUAL GENERATED' AND
                                               (CHARACTER_SET_NAME != 'utf8mb4' OR COLLATION_NAME != 'utf8mb4_unicode_ci')"
        );
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * @return array<string, mixed>
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    private function getTables(): array
    {
        $stmt = $this->connection->executeQuery(
            "SELECT TABLE_NAME, TABLE_COLLATION FROM information_schema.TABLES AS T INNER JOIN information_schema.COLLATION_CHARACTER_SET_APPLICABILITY AS C
            ON (C.collation_name = T.table_collation) WHERE 
            T.TABLE_SCHEMA = '{$this->connection->getDatabase()}' AND 
            (C.CHARACTER_SET_NAME != 'utf8mb4' OR C.COLLATION_NAME != 'utf8mb4_unicode_ci')"
        );
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
