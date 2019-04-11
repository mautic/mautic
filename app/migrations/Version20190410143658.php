<?php

/*
 * @package     Mautic
 * @copyright   2019 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Migrations\SkipMigrationException;

class Version20190410143658 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     *
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema)
    {
        $newIndexName = $this->getNewIndexName();
        $tableName = $this->getTableName();
        $table = $schema->getTable($tableName);

        if ($table->hasIndex($newIndexName) === true) {

            throw new SkipMigrationException('Schema includes this migration');
        }
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $tableName = $this->getTableName();
        $oldIndexName = $this->getOldIndexName($tableName);
        $newIndexName = $this->getNewIndexName();

        $this->addSql("ALTER TABLE {$tableName} ADD INDEX {$newIndexName} (lead_id,channel,reason);");
        $this->addSql("ALTER TABLE {$tableName} DROP INDEX {$oldIndexName};");
    }

    /**
     * @param string $tableName
     *
     * @return string
     */
    private function getOldIndexName($tableName)
    {
        $indexName = $this->generatePropertyName($tableName, 'idx', ['lead_id']);
        return $indexName;
    }

    /**
     * @return string
     */
    private function getNewIndexName()
    {
        $indexName = "{$this->prefix}leadid_reason_channel";
        return $indexName;
    }

    /**
     * @return string
     */
    private function getTableName()
    {
        $tableName = "{$this->prefix}lead_donotcontact";
        return $tableName;
    }

}
