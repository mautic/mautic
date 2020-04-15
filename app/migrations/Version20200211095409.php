<?php

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

/**
 * Migration for removing online status.
 */
class Version20200211095409 extends AbstractMauticMigration
{
    /**
     * @throws SkipMigration
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema): void
    {
        if (!$schema->getTable("{$this->prefix}users")->hasColumn('online_status')) {
            throw new SkipMigration("The online_status column on the {$this->prefix}users table has already been removed.");
        }
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE {$this->prefix}users DROP online_status");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE {$this->prefix}users ADD online_status VARCHAR(255) DEFAULT NULL");
    }
}
