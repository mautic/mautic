<?php

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

class Version20190410143658 extends AbstractMauticMigration
{
    /**
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema): void
    {
        $newIndexName = $this->getNewIndexName();
        $tableName    = $this->getTableName();
        $table        = $schema->getTable($tableName);

        if (true === $table->hasIndex($newIndexName)) {
            throw new SkipMigration('Schema includes this migration');
        }
    }

    /**
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function up(Schema $schema): void
    {
        $newIndexName = $this->getNewIndexName();
        $tableName    = $this->getTableName();
        $oldIndexName = $this->getOldIndexName($tableName);

        $this->addSql("ALTER TABLE {$tableName} ADD INDEX {$newIndexName} (lead_id, channel, reason);");
        if ($schema->getTable($tableName)->hasIndex($oldIndexName)) {
            $this->addSql("ALTER TABLE {$tableName} DROP INDEX {$oldIndexName};");
        }
    }

    /**
     * @param string $tableName
     *
     * @return string
     */
    private function getOldIndexName($tableName)
    {
        return $this->generatePropertyName($tableName, 'idx', ['lead_id']);
    }

    /**
     * @return string
     */
    private function getNewIndexName()
    {
        return "{$this->prefix}leadid_reason_channel";
    }

    /**
     * @return string
     */
    private function getTableName()
    {
        return "{$this->prefix}lead_donotcontact";
    }
}
