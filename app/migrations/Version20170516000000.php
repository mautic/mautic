<?php

namespace Mautic\Migrations;

use Doctrine\DBAL\Migrations\SkipMigrationException;
use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;
use Mautic\LeadBundle\Entity\LeadField;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170516000000 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     *
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema)
    {
        $em = $this->container->get('doctrine')->getManager();
        $leadField = $em->getRepository(LeadField::class)->findOneByAlias('points');

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
VALUES (1, 'Points', 'points', 'number', 'core', '0', 0, 1, 1, 0, 0, 0, 0, 29, 'a:0:{}', 'lead');
SQL;
        $this->addSql($sql);
    }
}
