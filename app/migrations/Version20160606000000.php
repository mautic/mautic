<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
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
 * Class Version20160606000000.
 */
class Version20160606000000 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     *
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema)
    {
        $table = $schema->getTable(MAUTIC_TABLE_PREFIX.'leads');
        if ($table->hasColumn('attribution')) {
            throw new SkipMigrationException('Schema includes this migration');
        }
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $sql = <<<SQL
INSERT INTO `{$this->prefix}lead_fields` (`is_published`, `label`, `alias`, `type`, `field_group`, `default_value`, `is_required`, `is_fixed`, `is_visible`, `is_short_visible`, `is_listable`, `is_publicly_updatable`, `is_unique_identifer`, `field_order`, `properties`) 
VALUES 
  (1,'Attribution', 'attribution', 'number', 'core', '0', 0, 1, 1, 0, 1, 0, 0, 23, 'a:2:{s:9:\"roundmode\";s:1:\"4\";s:9:\"precision\";s:1:\"2\";}'),
  (1,'Attribution Date', 'attribution_date', 'date', 'core', NULL , 0, 1, 1, 0, 1, 0, 0, 24, 'a:0:{}')
SQL;
        $this->addSql($sql);

        $this->addSql("ALTER TABLE {$this->prefix}leads ADD COLUMN attribution double DEFAULT NULL");
        $this->addSql("ALTER TABLE {$this->prefix}leads ADD COLUMN attribution_date date DEFAULT NULL");
        $this->addSql("CREATE INDEX {$this->prefix}contact_attribution ON {$this->prefix}leads (attribution, attribution_date)");
        $this->addSql("CREATE INDEX {$this->prefix}attribution_search ON {$this->prefix}leads (attribution)");
        $this->addSql("CREATE INDEX {$this->prefix}attribution_date_search ON {$this->prefix}leads (attribution_date)");

        $this->addSql("CREATE INDEX {$this->prefix}event_type ON {$this->prefix}campaign_events (event_type)");
        $this->addSql("CREATE INDEX {$this->prefix}campaign_leads ON {$this->prefix}campaign_leads (campaign_id, manually_removed, date_added, lead_id)");
        $this->addSql("CREATE INDEX {$this->prefix}campaign_leads ON {$this->prefix}campaign_lead_event_log (lead_id, campaign_id)");
    }
}
