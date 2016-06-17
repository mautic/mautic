<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Doctrine\DBAL\Migrations\SkipMigrationException;
use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

/**
 * Class Version20160606000000
 */
class Version20160606000000 extends AbstractMauticMigration
{
    private $keys = [];

    /**
     * @param Schema $schema
     *
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema)
    {
        if ($schema->hasTable($this->prefix.'lead_attributions')) {

            throw new SkipMigrationException('Schema includes this migration');
        }

        $this->keys = [
            'idx' => [
                'lead'     => $this->generatePropertyName('lead_attributions', 'idx', ['lead_id']),
                'campaign' => $this->generatePropertyName('lead_attributions', 'idx', ['campaign_id']),
                'ip'       => $this->generatePropertyName('lead_attributions', 'idx', ['ip_id']),
            ],
            'fk'  => [
                'lead'     => $this->generatePropertyName('lead_attributions', 'fk', ['lead_id']),
                'campaign' => $this->generatePropertyName('lead_attributions', 'fk', ['campaign_id']),
                'ip'       => $this->generatePropertyName('lead_attributions', 'fk', ['ip_id']),
            ],
        ];
    }

    /**
     * @param Schema $schema
     */
    public function mysqlUp(Schema $schema)
    {
        $sql = <<<SQL
INSERT INTO `{$this->prefix}lead_fields` (
  `is_published`, 
  `label`, 
  `alias`, 
  `type`, 
  `field_group`, 
  `default_value`, 
  `is_required`, 
  `is_fixed`, 
  `is_visible`, 
  `is_short_visible`, 
  `is_listable`, 
  `is_publicly_updatable`, 
  `is_unique_identifer`, 
  `field_order`, 
  `properties`
) VALUES (
  1,
  'Attribution', 
  'attribution', 
  'number', 
  'core', 
  '0', 
  0, 
  1, 
  1, 
  0, 
  1, 
  0, 
  0, 
  23, 
  'a:2:{s:9:\"roundmode\";s:1:\"4\";s:9:\"precision\";s:1:\"2\";}'
)
SQL;
        $this->addSql($sql);

        $this->addSql("ALTER TABLE {$this->prefix}leads ADD COLUMN attribution longtext");

        $sql = <<<SQL
CREATE TABLE {$this->prefix}lead_attributions (
  id INT AUTO_INCREMENT NOT NULL, 
  lead_id INT NOT NULL, 
  campaign_id INT DEFAULT NULL, 
  campaign_name LONGTEXT DEFAULT NULL,
  date_added DATETIME NOT NULL COMMENT '(DC2Type:datetime)', 
  channel VARCHAR(255) NOT NULL, 
  channel_id INT DEFAULT NULL, 
  action VARCHAR(255) NOT NULL,  
  stage_id INT DEFAULT NULL, 
  stage_name LONGTEXT DEFAULT NULL,
  comments LONGTEXT DEFAULT NULL, 
  attribution DOUBLE PRECISION NOT NULL,
  ip_id INT NOT NULL,
  INDEX {$this->keys['idx']['lead']} (lead_id), 
  INDEX {$this->keys['idx']['campaign']} (campaign_id), 
  INDEX {$this->keys['idx']['ip']} (ip_id),
  INDEX {$this->prefix}attribution_channel (channel, date_added), 
  INDEX {$this->prefix}attribution_channel_specific (channel, channel_id, date_added), 
  INDEX {$this->prefix}attribution_channel_lead (channel, lead_id, date_added), 
  INDEX {$this->prefix}attribution_channel_specific_lead (channel, channel_id, lead_id, date_added),
  INDEX {$this->prefix}attribution_channel_action_specific (channel, channel_id, action),
  INDEX {$this->prefix}attribution_channel_action_specific_lead (channel, channel_id, action, lead_id, date_added),
  INDEX {$this->prefix}attribution_campaign_lead (campaign_id, lead_id, date_added), 
  INDEX {$this->prefix}attribution_stage (stage_id, date_added), 
  INDEX {$this->prefix}attribution_stage_lead (stage_id, lead_id, date_added), 
  INDEX {$this->prefix}attribution_stage_campaign (stage_id, campaign_id, date_added),
  INDEX {$this->prefix}attribution_stage_campaign_lead (stage_id, campaign_id, lead_id, date_added),
  INDEX {$this->prefix}attribution_stage_channel (stage_id, channel, date_added),
  INDEX {$this->prefix}attribution_stage_channel_lead (stage_id, channel, lead_id, date_added),
  INDEX {$this->prefix}attribution_campaign (campaign_id, date_added),
  INDEX {$this->prefix}attribution_lead (lead_id, date_added),
  INDEX {$this->prefix}attribution_date_added (date_added),
  INDEX {$this->prefix}attribution_date_added_lead (date_added, lead_id),
  PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB
SQL;
        $this->addSql($sql);

        $this->addSql(
            "ALTER TABLE {$this->prefix}lead_attributions ADD CONSTRAINT {$this->keys['fk']['lead']} FOREIGN KEY (lead_id) REFERENCES {$this->prefix}leads (id) ON DELETE CASCADE"
        );
        $this->addSql(
            "ALTER TABLE {$this->prefix}lead_attributions ADD CONSTRAINT {$this->keys['fk']['campaign']} FOREIGN KEY (campaign_id) REFERENCES {$this->prefix}campaign_events (id) ON DELETE SET NULL"
        );

        $this->addSql(
            "ALTER TABLE {$this->prefix}lead_attributions ADD CONSTRAINT {$this->keys['fk']['ip']} FOREIGN KEY (ip_id) REFERENCES {$this->prefix}ip_addresses (id) ON DELETE SET NULL"
        );
    }

    /**
     * @param Schema $schema
     */
    public function postgresqlUp(Schema $schema)
    {

    }
}
