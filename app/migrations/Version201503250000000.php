<?php

namespace Mautic\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Migrations\SkipMigrationException;
use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;
use Mautic\LeadBundle\Entity\ListLead;

/**
 * Schema update for Version 1.0.0-rc4 to 1.0.0
 *
 * Class Version20150310000000
 *
 * @package Mautic\Migrations
 */
class Version20150310000000 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        parent::up($schema);

        $sm = $this->factory->getDatabase()->getSchemaManager();

    }

    /**
     * @param Schema $schema
     */
    public function mysqlUp(Schema $schema)
    {
        // see up
    }

    /**
     * @param Schema $schema
     */
    public function postgresqlUp(Schema $schema)
    {
        // see up
    }

    /**
     * @param Schema $schema
     */
    public function mssqlUp(Schema $schema)
    {
        // see up
    }
}