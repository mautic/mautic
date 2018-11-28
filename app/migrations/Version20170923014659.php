<?php

namespace Mautic\Migrations;

use Doctrine\DBAL\Migrations\SkipMigrationException;
use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170923014659 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql('ALTER TABLE '.$this->prefix.'pages ADD is_preference_center tinyint(1) DEFAULT NULL');

        $idx = $this->generatePropertyName('emails', 'idx', ['preference_center_id']);
        $fk  = $this->generatePropertyName('emails', 'fk', ['preference_center_id']);

        $this->addSql("ALTER TABLE {$this->prefix}emails ADD preference_center_id int DEFAULT NULL");
        $this->addSql("ALTER TABLE {$this->prefix}emails ADD CONSTRAINT $fk FOREIGN KEY (preference_center_id) REFERENCES {$this->prefix}pages (id) ON DELETE SET NULL");
        $this->addSql("CREATE INDEX $idx ON {$this->prefix}emails (preference_center_id)");
    }

    /**
     * @param Schema $schema
     *
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema)
    {
        $table1 = $schema->getTable($this->prefix.'pages');
        $table2 = $schema->getTable($this->prefix.'emails');

        if ($table1->hasColumn('is_preference_center') || $table2->hasColumn('preference_center_id')) {
            throw new SkipMigrationException('Schema includes this migration');
        }
    }
}
