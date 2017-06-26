<?php

namespace Mautic\Migrations;

use Doctrine\DBAL\Migrations\SkipMigrationException;
use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170429092049 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     *
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema)
    {
        if ($schema->getTable($this->prefix.'emails')->hasColumn('utm_tags') && $schema->getTable($this->prefix.'push_notifications')->hasColumn('utm_tags')) {
            throw new SkipMigrationException('Schema includes this migration');
        }
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql("ALTER TABLE {$this->prefix}emails ADD utm_tags LONGTEXT DEFAULT NULL COMMENT '(DC2Type:array)';");
        $this->addSql("ALTER TABLE {$this->prefix}push_notifications ADD utm_tags LONGTEXT DEFAULT NULL COMMENT '(DC2Type:array)';");
    }
}
