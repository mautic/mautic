<?php

namespace Mautic\Migrations;

use Doctrine\DBAL\Migrations\SkipMigrationException;
use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

/**
 * Migration for removing online status.
 */
class Version20200211095409 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     *
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema)
    {
        if (!$schema->getTable("{$this->prefix}users")->hasColumn('online_status')) {
            throw new SkipMigrationException("The online_status column on the {$this->prefix}users table has already been removed.");
        }
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql("ALTER TABLE {$this->prefix}users DROP online_status");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql("ALTER TABLE {$this->prefix}users ADD online_status VARCHAR(255) DEFAULT NULL");
    }
}
