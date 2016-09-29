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
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160916000000 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     */
    public function preUp(Schema $schema)
    {
        if ($schema->hasTable($this->prefix.'message_queue')) {

            throw new SkipMigrationException('Schema includes this migration');
        }
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $leadIDX  = $this->generatePropertyName('message_queue', 'idx', ['lead_id']);
        $leadFK   = $this->generatePropertyName('message_queue', 'fk', ['lead_id']);
        $campaignFK    = $this->generatePropertyName('message_queue', 'idx', ['campaign_id']);

        $sql = <<<SQL
CREATE TABLE {$this->prefix}message_queue (
  `id` INT AUTO_INCREMENT NOT NULL,
  `channel` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `channel_id` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `campaign_id` int(11) DEFAULT NULL,
  `priority` int(11) NOT NULL DEFAULT '0',
  `max_attempts` tinyint(3) unsigned NOT NULL DEFAULT '3',
  `attempts` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `success` tinyint(1) NOT NULL DEFAULT '0',
  `status` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'pending',
  `date_published` datetime DEFAULT NULL,
  `scheduled_date` datetime DEFAULT NULL,
  `last_attempt` datetime DEFAULT NULL,
  `date_sent` datetime DEFAULT NULL,
  `options` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `event_id` int(11) DEFAULT NULL,
  `lead_id` int(11) DEFAULT NULL,
  INDEX {$leadIDX} (lead_id),
  INDEX {$this->prefix}message_date_sent (date_sent),
  INDEX {$this->prefix}message_date_scheduled (scheduled_date),
  INDEX {$this->prefix}message_priority (priority),
  INDEX {$this->prefix}message_success (success),
	PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;
SQL;
        $this->addSql($sql);

        $this->addSql("ALTER TABLE {$this->prefix}message_queue ADD CONSTRAINT {$leadFK} FOREIGN KEY (lead_id) REFERENCES {$this->prefix}leads (id) ON DELETE SET NULL;");
        $this->addSql("ALTER TABLE {$this->prefix}message_queue ADD CONSTRAINT {$campaignFK} FOREIGN KEY (campaign_id) REFERENCES {$this->prefix}campaigns (id);");

        $this->addSql("CREATE INDEX {$this->prefix}message_channel_search ON {$this->prefix}message_queue (channel, channel_id)");
        $this->addSql("CREATE INDEX {$this->prefix}message_lead_search ON {$this->prefix}message_queue (lead_id)");

    }
}