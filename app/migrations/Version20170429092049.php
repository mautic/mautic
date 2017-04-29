<?php

namespace Mautic\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170429092049 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $columns = $schema->getTable($this->prefix.'emails')->getColumns();

        if (array_key_exists('utm_tags', $columns)) {
            throw new SkipMigrationException('Schema includes this migration');
        }

    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql("ALTER TABLE {$this->prefix}emails ADD utm_tags LONGTEXT DEFAULT NULL COMMENT '(DC2Type:array)';");
    }
}
