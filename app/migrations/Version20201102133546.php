<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        https://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

/**
 * Class Version20201102133546.
 */
final class Version20201102133546 extends AbstractMauticMigration
{
    private $indexName;
    private $columnName = 'email_id';

    public function preUp(Schema $schema): void
    {
        $schemaName = $schema->getName();
        $tableName  = $this->getTableName();
        $sql        = <<<SQL
            SELECT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS
            WHERE INDEX_SCHEMA='$schemaName' AND TABLE_NAME='$tableName'
            AND COLUMN_NAME='$this->columnName' AND INDEX_NAME<>'PRIMARY' LIMIT 1;
SQL;

        $stmt = $this->connection->prepare($sql);
        $stmt->execute();
        $this->indexName = $stmt->fetchColumn();
        $stmt->closeCursor();

        if (!$this->indexName) {
            throw new SkipMigration('Schema includes this migration');
        }
    }

    /**
     * @throws DBALException
     * @throws SchemaException
     */
    public function up(Schema $schema): void
    {
        $schema->getTable($this->getTableName())->dropIndex($this->indexName);
    }

    public function down(Schema $schema): void
    {
        $tableName = $this->getTableName();
        $this->addSql(
            'ALTER TABLE '.$tableName.' ADD INDEX IDX_'.strtoupper($this->columnName).' ('.$this->columnName.')'
        );
    }

    private function getTableName(): string
    {
        return $this->prefix.'email_assets_xref';
    }
}
