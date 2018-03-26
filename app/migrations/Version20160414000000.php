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
 * Web Notification Channel Migration.
 */
class Version20160414000000 extends AbstractMauticMigration
{
    private $keys;

    /**
     * @param Schema $schema
     *
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema)
    {
        if ($schema->hasTable($this->prefix.'push_notifications')) {
            throw new SkipMigrationException('Schema includes this migration');
        }

        $this->keys = [
            'push_notifications' => [
                'idx' => [
                    'category' => $this->generatePropertyName('push_notifications', 'idx', ['category_id']),
                ],
                'fk' => [
                    'category' => $this->generatePropertyName('push_notifications', 'fk', ['category_id']),
                ],
            ],
            'push_notification_stats' => [
                'idx' => [
                    'notification' => $this->generatePropertyName('push_notification_stats', 'idx', ['notification_id']),
                    'lead'         => $this->generatePropertyName('push_notification_stats', 'idx', ['lead_id']),
                    'list'         => $this->generatePropertyName('push_notification_stats', 'idx', ['list_id']),
                    'ip'           => $this->generatePropertyName('push_notification_stats', 'idx', ['ip_id']),
                ],
                'fk' => [
                    'notification' => $this->generatePropertyName('push_notification_stats', 'fk', ['notification_id']),
                    'lead'         => $this->generatePropertyName('push_notification_stats', 'fk', ['lead_id']),
                    'list'         => $this->generatePropertyName('push_notification_stats', 'fk', ['list_id']),
                    'ip'           => $this->generatePropertyName('push_notification_stats', 'fk', ['ip_id']),
                ],
            ],
            'push_ids' => [
                'idx' => [
                    'lead' => $this->generatePropertyName('push_ids', 'idx', ['lead_id']),
                ],
                'fk' => [
                    'lead' => $this->generatePropertyName('push_ids', 'fk', ['lead_id']),
                ],
            ],
            'push_notification_list_xref' => [
                'idx' => [
                    'notification' => $this->generatePropertyName('push_notification_list_xref', 'idx', ['notification_id']),
                    'leadlist'     => $this->generatePropertyName('push_notification_list_xref', 'idx', ['leadlist_id']),
                ],
                'fk' => [
                    'notification' => $this->generatePropertyName('push_notification_list_xref', 'fk', ['notification_id']),
                    'leadlist'     => $this->generatePropertyName('push_notification_list_xref', 'fk', ['leadlist_id']),
                ],
            ],
        ];
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS {$this->prefix}push_notifications (
  id INT AUTO_INCREMENT NOT NULL,
  category_id INT DEFAULT NULL,
  is_published TINYINT(1) NOT NULL,
  date_added DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)',
  created_by INT DEFAULT NULL,
  created_by_user VARCHAR(255) DEFAULT NULL,
  date_modified DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)',
  modified_by INT DEFAULT NULL,
  modified_by_user VARCHAR(255) DEFAULT NULL,
  checked_out DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)',
  checked_out_by INT DEFAULT NULL,
  checked_out_by_user VARCHAR(255) DEFAULT NULL,
  name VARCHAR(255) NOT NULL,
  description LONGTEXT DEFAULT NULL,
  lang VARCHAR(255) NOT NULL,
  url LONGTEXT DEFAULT NULL,
  heading LONGTEXT NOT NULL,
  message LONGTEXT NOT NULL,
  notification_type LONGTEXT DEFAULT NULL,
  publish_up DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)',
  publish_down DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)',
  read_count INT NOT NULL,
  sent_count INT NOT NULL,
  INDEX {$this->keys['push_notifications']['idx']['category']} (category_id),
  PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB
SQL;
        $this->addSql($sql);

        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS {$this->prefix}push_notification_list_xref (
  notification_id INT NOT NULL,
  leadlist_id INT NOT NULL,
  INDEX {$this->keys['push_notification_list_xref']['idx']['notification']} (notification_id),
  INDEX {$this->keys['push_notification_list_xref']['idx']['leadlist']} (leadlist_id),
  PRIMARY KEY(notification_id, leadlist_id)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB
SQL;
        $this->addSql($sql);

        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS {$this->prefix}push_ids (
  id INT AUTO_INCREMENT NOT NULL,
  lead_id INT DEFAULT NULL,
  push_id VARCHAR(255) NOT NULL,
  INDEX {$this->keys['push_ids']['idx']['lead']} (lead_id),
  PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;
SQL;
        $this->addSql($sql);

        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS {$this->prefix}push_notification_stats (
  id INT AUTO_INCREMENT NOT NULL,
  notification_id INT DEFAULT NULL,
  lead_id INT DEFAULT NULL,
  list_id INT DEFAULT NULL,
  ip_id INT DEFAULT NULL,
  date_sent DATETIME NOT NULL COMMENT '(DC2Type:datetime)',
  is_clicked TINYINT(1) NOT NULL,
  date_clicked DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)',
  tracking_hash VARCHAR(255) DEFAULT NULL,
  retry_count INT DEFAULT NULL,
  source VARCHAR(255) DEFAULT NULL,
  source_id INT DEFAULT NULL,
  tokens LONGTEXT DEFAULT NULL COMMENT '(DC2Type:array)',
  click_count INT DEFAULT NULL,
  last_clicked DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)',
  click_details LONGTEXT DEFAULT NULL COMMENT '(DC2Type:array)',
  INDEX {$this->keys['push_notification_stats']['idx']['notification']} (notification_id),
  INDEX {$this->keys['push_notification_stats']['idx']['lead']} (lead_id),
  INDEX {$this->keys['push_notification_stats']['idx']['list']} (list_id),
  INDEX {$this->keys['push_notification_stats']['idx']['ip']} (ip_id),
  INDEX {$this->prefix}stat_notification_search (notification_id, lead_id),
  INDEX {$this->prefix}stat_notification_clicked_search (is_clicked),
  INDEX {$this->prefix}stat_notification_hash_search (tracking_hash),
  INDEX {$this->prefix}stat_notification_source_search (source, source_id),
  PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB
SQL;
        $this->addSql($sql);

        $this->addSql("ALTER TABLE {$this->prefix}push_notifications ADD CONSTRAINT {$this->keys['push_notifications']['fk']['category']} FOREIGN KEY (category_id) REFERENCES {$this->prefix}categories (id) ON DELETE SET NULL");
        $this->addSql("ALTER TABLE {$this->prefix}push_notification_list_xref ADD CONSTRAINT {$this->keys['push_notification_list_xref']['fk']['notification']} FOREIGN KEY (notification_id) REFERENCES {$this->prefix}push_notifications (id) ON DELETE CASCADE");
        $this->addSql("ALTER TABLE {$this->prefix}push_notification_list_xref ADD CONSTRAINT {$this->keys['push_notification_list_xref']['fk']['leadlist']} FOREIGN KEY (leadlist_id) REFERENCES {$this->prefix}lead_lists (id) ON DELETE CASCADE");
        $this->addSql("ALTER TABLE {$this->prefix}push_ids ADD CONSTRAINT {$this->keys['push_ids']['fk']['lead']} FOREIGN KEY (lead_id) REFERENCES {$this->prefix}leads (id) ON DELETE SET NULL");
        $this->addSql("ALTER TABLE {$this->prefix}push_notification_stats ADD CONSTRAINT {$this->keys['push_notification_stats']['fk']['notification']} FOREIGN KEY (notification_id) REFERENCES {$this->prefix}push_notifications (id) ON DELETE SET NULL");
        $this->addSql("ALTER TABLE {$this->prefix}push_notification_stats ADD CONSTRAINT {$this->keys['push_notification_stats']['fk']['lead']} FOREIGN KEY (lead_id) REFERENCES {$this->prefix}leads (id) ON DELETE SET NULL");
        $this->addSql("ALTER TABLE {$this->prefix}push_notification_stats ADD CONSTRAINT {$this->keys['push_notification_stats']['fk']['list']} FOREIGN KEY (list_id) REFERENCES {$this->prefix}lead_lists (id) ON DELETE SET NULL");
        $this->addSql("ALTER TABLE {$this->prefix}push_notification_stats ADD CONSTRAINT {$this->keys['push_notification_stats']['fk']['ip']} FOREIGN KEY (ip_id) REFERENCES {$this->prefix}ip_addresses (id)");
    }
}
