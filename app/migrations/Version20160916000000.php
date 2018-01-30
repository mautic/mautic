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
        $eventIdx = $this->generatePropertyName('message_queue', 'idx', ['event_id']);
        $eventFk  = $this->generatePropertyName('message_queue', 'fk', ['event_id']);

        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS {$this->prefix}message_queue (
  id INT AUTO_INCREMENT NOT NULL,
  event_id INT DEFAULT NULL,
  lead_id INT NOT NULL,
  channel VARCHAR(255) NOT NULL,
  channel_id INT NOT NULL,
  priority SMALLINT NOT NULL,
  max_attempts SMALLINT NOT NULL,
  attempts SMALLINT NOT NULL,
  success TINYINT(1) NOT NULL,
  status VARCHAR(255) NOT NULL,
  date_published DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)',
  scheduled_date DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)',
  last_attempt DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)',
  date_sent DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)',
  options LONGTEXT DEFAULT NULL COMMENT '(DC2Type:array)',
  INDEX $eventIdx (event_id),
  INDEX $leadIDX (lead_id),
  INDEX {$this->prefix}message_status_search (status),
  INDEX {$this->prefix}message_date_sent (date_sent),
  INDEX {$this->prefix}message_scheduled_date (scheduled_date),
  INDEX {$this->prefix}message_priority (priority),
  INDEX {$this->prefix}message_success (success),
  INDEX {$this->prefix}message_channel_search (channel, channel_id),
  PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;
SQL;
        $this->addSql($sql);

        $this->addSql("ALTER TABLE {$this->prefix}message_queue ADD CONSTRAINT {$leadFK} FOREIGN KEY (lead_id) REFERENCES {$this->prefix}leads (id) ON DELETE CASCADE");
        $this->addSql("ALTER TABLE {$this->prefix}message_queue ADD CONSTRAINT {$eventFk} FOREIGN KEY (event_id) REFERENCES {$this->prefix}campaign_events (id) ON DELETE CASCADE");
    }
}
