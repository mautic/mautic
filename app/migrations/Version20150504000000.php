<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Doctrine\DBAL\Migrations\SkipMigrationException;
use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

/**
 * Migration 1.0.3 to 1.0.4.
 */
class Version20150504000000 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     *
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema)
    {
        // Test to see if this migration has already been applied
        $table = $schema->getTable($this->prefix.'lead_fields');
        if ($table->hasColumn('is_unique_identifer')) {
            throw new SkipMigrationException('Schema includes this migration');
        }
    }

    /**
     * @param Schema $schema
     */
    public function postUp(Schema $schema)
    {
        // Set email as a unique ID
        $q = $this->connection->createQueryBuilder();
        $q->update(MAUTIC_TABLE_PREFIX.'lead_fields')
            ->set('is_unique_identifer', ':true')
            ->where(
                $q->expr()->eq('alias', $q->expr()->literal('email'))
            )
            ->setParameter('true', true, 'boolean')
            ->execute();
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql('ALTER TABLE '.$this->prefix.'campaign_lead_event_log DROP FOREIGN KEY '.$this->findPropertyName('campaign_lead_event_log', 'fk', 'F639F774'));
        $this->addSql('ALTER TABLE '.$this->prefix.'campaign_lead_event_log ADD metadata LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', ADD non_action_path_taken TINYINT(1) DEFAULT NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'campaign_lead_event_log ADD CONSTRAINT '.$this->generatePropertyName('campaign_lead_event_log', 'fk', ['campaign_id']).' FOREIGN KEY (campaign_id) REFERENCES '.$this->prefix.'campaigns (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE '.$this->prefix.'lead_fields ADD is_unique_identifer TINYINT(1) DEFAULT NULL');
    }
}
