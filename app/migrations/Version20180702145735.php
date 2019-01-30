<?php

/*
 * @package     Mautic
 * @copyright   2018 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Doctrine\DBAL\Migrations\SkipMigrationException;
use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;
use Mautic\LeadBundle\Entity\LeadField;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180702145735 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     *
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema)
    {
        $em        = $this->container->get('doctrine')->getManager();
        $leadField = $em->getRepository(LeadField::class)->findOneByAlias('last_active');

        if ($leadField) {
            throw new SkipMigrationException('Schema includes this migration');
        }
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $sql = <<<SQL
INSERT INTO `{$this->prefix}lead_fields` (`is_published`, `label`, `alias`, `type`, `field_group`, `default_value`, `is_required`, `is_fixed`, `is_visible`, `is_short_visible`, `is_listable`, `is_publicly_updatable`, `is_unique_identifer`, `field_order`, `properties`, `object`) 
VALUES 
    (1, 'Date Last Active', 'last_active', 'datetime', 'core', NULL , 0, 1, 1, 0, 1, 0, 0, 24, 'a:0:{}', 'lead')
SQL;
        $this->addSql($sql);
        // create index just if not exist
        if (!$schema->getTable($this->prefix.'leads')->hasIndex($this->prefix.'last_active_search')) {
            $this->addSql("CREATE INDEX {$this->prefix}last_active_search ON {$this->prefix}leads (last_active)");
        }
    }
}
