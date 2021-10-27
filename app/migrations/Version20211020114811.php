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
