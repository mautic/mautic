<?php

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20191206113956 extends AbstractMauticMigration
{
    /**
     * @throws SkipMigration
     * @throws SchemaException
     */
    public function preUp(Schema $schema): void
    {
        $table = $schema->getTable($this->prefix.'lead_lists');

        if ($table->hasColumn('public_name')) {
            throw new SkipMigration('Schema includes this migration');
        }
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE {$this->prefix}lead_lists ADD public_name VARCHAR(255) DEFAULT '' NOT NULL");
        $this->addSql("UPDATE {$this->prefix}lead_lists SET public_name=name");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("{$this->prefix}ALTER TABLE lead_lists DROP public_name");
    }
}
