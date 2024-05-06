<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20211020114811 extends AbstractMauticMigration
{
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

        $dropIndexQuery = 'DROP INDEX %s on %s';

        $this->addSql(
            sprintf(
                $dropIndexQuery,
                $this->prefix.'company_match',
                $this->getPrefixedTableName('companies')
            )
        );
        $this->addSql(
            sprintf(
                'CREATE INDEX %s on %s(`companyname`(191),`companycity`(191),`companycountry`(191),`companystate`(191))',
                $this->prefix.'company_match',
                $this->getPrefixedTableName('companies')
            )
        );

        $this->addSql(
            sprintf(
                $dropIndexQuery,
                $this->prefix.'integration_object',
                $this->getPrefixedTableName('sync_object_mapping')
            )
        );
        $this->addSql(
            sprintf(
                'CREATE INDEX %s on %s(`integration`(191),`integration_object_name`(191),`integration_object_id`(191), `integration_reference_id`(191))',
                $this->prefix.'integration_object',
                $this->getPrefixedTableName('sync_object_mapping')
            )
        );

        $this->addSql(
            sprintf(
                $dropIndexQuery,
                $this->prefix.'integration_reference',
                $this->getPrefixedTableName('sync_object_mapping')
            )
        );
        $this->addSql(
            sprintf(
                'CREATE INDEX %s on %s(`integration`(191),`integration_object_name`(191), `integration_reference_id`(191), `integration_object_id`(191))',
                $this->prefix.'integration_reference',
                $this->getPrefixedTableName('sync_object_mapping')
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

    private function getPrefixedTableName(string $tableName): string
    {
        return $this->prefix.$tableName;
    }
}
