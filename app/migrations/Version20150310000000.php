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
    public function preUp(Schema $schema)
    {
        $this->connection->delete(MAUTIC_TABLE_PREFIX . 'addons', array('bundle' => 'MauticChatBundle'));
    }

    /**
     * @param Schema $schema
     */
    public function mysqlUp(Schema $schema)
    {
        // see preUp
    }

    /**
     * @param Schema $schema
     */
    public function postgresqlUp(Schema $schema)
    {
        // see preUp
    }

    /**
     * @param Schema $schema
     */
    public function mssqlUp(Schema $schema)
    {
        // see preUp
    }
}