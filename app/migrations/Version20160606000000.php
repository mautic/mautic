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
            ],
            'fk'  => [
                'lead'     => $this->generatePropertyName('lead_attributions', 'fk', ['lead_id']),
                'campaign' => $this->generatePropertyName('lead_attributions', 'fk', ['campaign_id']),
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
  date_added DATETIME NOT NULL COMMENT '(DC2Type:datetime)', 
  channel VARCHAR(255) NOT NULL, 
  channel_id INT DEFAULT NULL, 
  stage_id INT DEFAULT NULL, 
  comments LONGTEXT DEFAULT NULL, 
  attribution DOUBLE PRECISION NOT NULL, 
  INDEX {$this->keys['idx']['lead']} (lead_id), 
  INDEX {$this->keys['idx']['campaign']} (campaign_id), 
  INDEX {$this->prefix}attribution_channel (channel, date_added), 
  INDEX {$this->prefix}attribution_channel_specific (channel, channel_id, date_added), 
  INDEX {$this->prefix}attribution_channel_lead (channel, lead_id, date_added), 
  INDEX {$this->prefix}attribution_channel_specific_lead (channel, channel_id, lead_id, date_added), 
  INDEX {$this->prefix}attribution_campaign_lead (campaign_id, lead_id, date_added), 
  INDEX {$this->prefix}attribution_stage (stage_id, date_added), 
  INDEX {$this->prefix}attribution_stage_lead (stage_id, lead_id, date_added), 
  INDEX {$this->prefix}attribution_stage_campaign (stage_id, campaign_id, date_added),
  INDEX {$this->prefix}attribution_stage_campaign_lead (stage_id, campaign_id, lead_id, date_added),
  INDEX {$this->prefix}attribution_stage_channel (stage_id, channel, date_added),
  INDEX {$this->prefix}attribution_stage_channel_lead (stage_id, channel, lead_id, date_added),
  PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB
SQL;
        $this->addSql($sql);

        $this->addSql("ALTER TABLE {$this->prefix}lead_attributions ADD CONSTRAINT {$this->keys['fk']['lead']} FOREIGN KEY (lead_id) REFERENCES {$this->prefix}leads (id) ON DELETE CASCADE");
        $this->addSql("ALTER TABLE {$this->prefix}lead_attributions ADD CONSTRAINT {$this->keys['fk']['campaign']} FOREIGN KEY (campaign_id) REFERENCES {$this->prefix}campaigns (id) ON DELETE SET NULL");
    }

    /**
     * @param Schema $schema
     */
    public function postgresqlUp(Schema $schema)
    {
        $nextId = $this->connection->executeQuery(
            "SELECT NEXTVAL('{$this->prefix}lead_fields_id_seq')"
        )->fetchColumn();

        $sql = <<<SQL
INSERT INTO "{$this->prefix}lead_fields" (
  "id",
  "is_published", 
  "label", 
  "alias", 
  "type", 
  "field_group", 
  "default_value", 
  "is_required", 
  "is_fixed", 
  "is_visible", 
  "is_short_visible", 
  "is_listable", 
  "is_publicly_updatable", 
  "is_unique_identifer", 
  "field_order", 
  "properties"
) VALUES (
  $nextId,
  true,
  'Attribution', 
  'attribution', 
  'number', 
  'core', 
  '0', 
  false, 
  true, 
  true, 
  false, 
  true, 
  false, 
  false, 
  23, 
  'a:2:{s:9:\"roundmode\";s:1:\"4\";s:9:\"precision\";s:1:\"2\";}'
)
SQL;
        $this->addSql($sql);

        $this->addSql('ALTER TABLE ' . $this->prefix . 'leads ADD COLUMN attribution text');

        $this->addSql("CREATE SEQUENCE {$this->prefix}lead_attributions_id_seq INCREMENT BY 1 MINVALUE 1 START 1");
        $sql = <<<SQL
CREATE TABLE {$this->prefix}lead_attributions (
    id INT NOT NULL, 
    lead_id INT NOT NULL, 
    campaign_id INT DEFAULT NULL, 
    date_added TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, 
    channel VARCHAR(255) NOT NULL, 
    channel_id INT DEFAULT NULL, 
    stage_id INT DEFAULT NULL, 
    comments TEXT DEFAULT NULL, 
    attribution DOUBLE PRECISION NOT NULL, 
PRIMARY KEY(id));
SQL;
        $this->addSql($sql);

        $this->addSql("CREATE INDEX {$this->keys['idx']['lead']} ON {$this->prefix}lead_attributions (lead_id)");
        $this->addSql("CREATE INDEX {$this->keys['idx']['campaign']} ON {$this->prefix}lead_attributions (campaign_id)");
        $this->addSql("CREATE INDEX {$this->prefix}attribution_channel ON {$this->prefix}lead_attributions (channel)");
        $this->addSql("CREATE INDEX {$this->prefix}attribution_channel_specific ON {$this->prefix}lead_attributions (channel, channel_id)");
        $this->addSql("CREATE INDEX {$this->prefix}attribution_channel_lead ON {$this->prefix}lead_attributions (channel, lead_id)");
        $this->addSql("CREATE INDEX {$this->prefix}attribution_channel_specific_lead ON {$this->prefix}lead_attributions (channel, channel_id, lead_id)");
        $this->addSql("CREATE INDEX {$this->prefix}attribution_campaign_lead ON {$this->prefix}lead_attributions (campaign_id, lead_id)");
        $this->addSql("CREATE INDEX {$this->prefix}attribution_stage ON {$this->prefix}lead_attributions (stage_id)");
        $this->addSql("CREATE INDEX {$this->prefix}attribution_stage_lead ON {$this->prefix}lead_attributions (stage_id, lead_id)");
        $this->addSql("CREATE INDEX {$this->prefix}attribution_stage_campaign ON {$this->prefix}lead_attributions (stage_id, campaign_id, date_added)");
        $this->addSql("CREATE INDEX {$this->prefix}attribution_stage_campaign_lead ON {$this->prefix}lead_attributions (stage_id, campaign_id, lead_id, date_added)");
        $this->addSql("CREATE INDEX {$this->prefix}attribution_stage_channel ON {$this->prefix}lead_attributions (stage_id, channel, date_added)");
        $this->addSql("CREATE INDEX {$this->prefix}attribution_stage_channel_lead ON {$this->prefix}lead_attributions (stage_id, channel, lead_id, date_added)");

        $this->addSql("COMMENT ON COLUMN {$this->prefix}lead_attributions.date_added IS '(DC2Type:datetime)'");
        $this->addSql("ALTER TABLE {$this->prefix}lead_attributions ADD CONSTRAINT {$this->keys['fk']['lead']} FOREIGN KEY (lead_id) REFERENCES {$this->prefix}leads (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE");
        $this->addSql("ALTER TABLE {$this->prefix}lead_attributions ADD CONSTRAINT {$this->keys['fk']['campaign']} FOREIGN KEY (campaign_id) REFERENCES {$this->prefix}campaigns (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE");
    }
}
