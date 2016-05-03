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
 * Web Notification Channel Migration
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

        $this->keys = array(
            'push_notifications'      => array(
                'idx' => array(
                    'category' => $this->generatePropertyName('push_notifications', 'idx', array('category_id'))
                ),
                'fk'  => array(
                    'category' => $this->generatePropertyName('push_notifications', 'fk', array('category_id'))
                )
            ),
            'push_notification_stats' => array(
                'idx' => array(
                    'notification' => $this->generatePropertyName('push_notification_stats', 'idx', array('notification_id')),
                    'lead'         => $this->generatePropertyName('push_notification_stats', 'idx', array('lead_id')),
                    'list'         => $this->generatePropertyName('push_notification_stats', 'idx', array('list_id')),
                    'ip'           => $this->generatePropertyName('push_notification_stats', 'idx', array('ip_id'))
                ),
                'fk'  => array(
                    'notification' => $this->generatePropertyName('push_notification_stats', 'fk', array('notification_id')),
                    'lead'         => $this->generatePropertyName('push_notification_stats', 'fk', array('lead_id')),
                    'list'         => $this->generatePropertyName('push_notification_stats', 'fk', array('list_id')),
                    'ip'           => $this->generatePropertyName('push_notification_stats', 'fk', array('ip_id'))
                )
            ),
            'push_ids' => array(
                'idx' =>  array(
                    'lead' => $this->generatePropertyName('push_ids', 'idx', array('lead_id'))
                ),
                'fk' => array(
                    'lead' => $this->generatePropertyName('push_ids', 'fk', array('lead_id'))
                )
            ),
            'push_notification_list_xref'   => array(
                'idx' => array(
                    'notification' => $this->generatePropertyName('push_notification_list_xref', 'idx', array('notification_id')),
                    'leadlist'     => $this->generatePropertyName('push_notification_list_xref', 'idx', array('leadlist_id'))
                ),
                'fk'  => array(
                    'notification' => $this->generatePropertyName('push_notification_list_xref', 'fk', array('notification_id')),
                    'leadlist'     => $this->generatePropertyName('push_notification_list_xref', 'fk', array('leadlist_id'))
                )
            )
        );
    }

    /**
     * @param Schema $schema
     */
    public function mysqlUp(Schema $schema)
    {
        $sql = <<<SQL
CREATE TABLE {$this->prefix}push_notifications (
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
CREATE TABLE {$this->prefix}push_notification_list_xref (
  notification_id INT NOT NULL, 
  leadlist_id INT NOT NULL, 
  INDEX {$this->keys['push_notification_list_xref']['idx']['notification']} (notification_id), 
  INDEX {$this->keys['push_notification_list_xref']['idx']['leadlist']} (leadlist_id), 
  PRIMARY KEY(notification_id, leadlist_id)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB
SQL;
        $this->addSql($sql);

        $sql = <<<SQL
CREATE TABLE {$this->prefix}push_ids (
  id INT AUTO_INCREMENT NOT NULL, 
  lead_id INT DEFAULT NULL, 
  push_id VARCHAR(255) NOT NULL, 
  INDEX {$this->keys['push_ids']['idx']['lead']} (lead_id), 
  PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;
SQL;
        $this->addSql($sql);

        $sql = <<<SQL
CREATE TABLE {$this->prefix}push_notification_stats (
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

    /**
     * @param Schema $schema
     */
    public function postgresqlUp(Schema $schema)
    {
        $this->addSql("CREATE SEQUENCE {$this->prefix}push_notifications_id_seq INCREMENT BY 1 MINVALUE 1 START 1");
        $this->addSql("CREATE SEQUENCE {$this->prefix}push_ids_id_seq INCREMENT BY 1 MINVALUE 1 START 1");
        $this->addSql("CREATE SEQUENCE {$this->prefix}push_notification_stats_id_seq INCREMENT BY 1 MINVALUE 1 START 1");

        $sql = <<<SQL
CREATE TABLE {$this->prefix}push_notifications (
  id INT NOT NULL, 
  category_id INT DEFAULT NULL, 
  is_published BOOLEAN NOT NULL, 
  date_added TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, 
  created_by INT DEFAULT NULL, 
  created_by_user VARCHAR(255) DEFAULT NULL, 
  date_modified TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, 
  modified_by INT DEFAULT NULL, 
  modified_by_user VARCHAR(255) DEFAULT NULL, 
  checked_out TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, 
  checked_out_by INT DEFAULT NULL, 
  checked_out_by_user VARCHAR(255) DEFAULT NULL, 
  name VARCHAR(255) NOT NULL, 
  description TEXT DEFAULT NULL, 
  lang VARCHAR(255) NOT NULL, 
  url TEXT DEFAULT NULL, 
  heading TEXT NOT NULL, 
  message TEXT NOT NULL, 
  notification_type TEXT DEFAULT NULL, 
  publish_up TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, 
  publish_down TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, 
  read_count INT NOT NULL, 
  sent_count INT NOT NULL, PRIMARY KEY(id)
)
SQL;
        $this->addSql($sql);
        $this->addSql("CREATE INDEX {$this->keys['push_notifications']['idx']['category']} ON {$this->prefix}push_notifications (category_id)");
        $this->addSql("COMMENT ON COLUMN {$this->prefix}push_notifications.date_added IS '(DC2Type:datetime)'");
        $this->addSql("COMMENT ON COLUMN {$this->prefix}push_notifications.date_modified IS '(DC2Type:datetime)'");
        $this->addSql("COMMENT ON COLUMN {$this->prefix}push_notifications.checked_out IS '(DC2Type:datetime)'");
        $this->addSql("COMMENT ON COLUMN {$this->prefix}push_notifications.publish_up IS '(DC2Type:datetime)'");
        $this->addSql("COMMENT ON COLUMN {$this->prefix}push_notifications.publish_down IS '(DC2Type:datetime)'");

        $sql = <<<SQL
CREATE TABLE {$this->prefix}push_notification_list_xref (
  notification_id INT NOT NULL, 
  leadlist_id INT NOT NULL, 
  PRIMARY KEY(notification_id, leadlist_id)
)
SQL;
        $this->addSql($sql);
        $this->addSql("CREATE INDEX {$this->keys['push_notification_list_xref']['idx']['notification']} ON {$this->prefix}push_notification_list_xref (notification_id)");
        $this->addSql("CREATE INDEX {$this->keys['push_notification_list_xref']['idx']['leadlist']} ON {$this->prefix}push_notification_list_xref (leadlist_id)");

        $sql = <<<SQL
CREATE TABLE {$this->prefix}push_ids (
  id INT NOT NULL, 
  lead_id INT DEFAULT NULL, 
  push_id VARCHAR(255) NOT NULL, 
  PRIMARY KEY(id)
)
SQL;
        $this->addSql($sql);
        $this->addSql("CREATE INDEX {$this->keys['push_ids']['idx']['lead']} ON {$this->prefix}push_ids (lead_id)");

        $sql = <<<SQL
CREATE TABLE {$this->prefix}push_notification_stats (
  id INT NOT NULL, 
  notification_id INT DEFAULT NULL, 
  lead_id INT DEFAULT NULL, 
  list_id INT DEFAULT NULL, 
  ip_id INT DEFAULT NULL, 
  date_sent TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, 
  is_clicked BOOLEAN NOT NULL, 
  date_clicked TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, 
  tracking_hash VARCHAR(255) DEFAULT NULL, 
  retry_count INT DEFAULT NULL, 
  source VARCHAR(255) DEFAULT NULL, 
  source_id INT DEFAULT NULL, 
  tokens TEXT DEFAULT NULL, 
  click_count INT DEFAULT NULL, 
  last_clicked TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, 
  click_details TEXT DEFAULT NULL, 
  PRIMARY KEY(id)
)
SQL;
        $this->addSql($sql);

        $this->addSql("CREATE INDEX {$this->keys['push_notification_stats']['idx']['notification']} ON {$this->prefix}push_notification_stats (notification_id)");
        $this->addSql("CREATE INDEX {$this->keys['push_notification_stats']['idx']['lead']} ON {$this->prefix}push_notification_stats (lead_id)");
        $this->addSql("CREATE INDEX {$this->keys['push_notification_stats']['idx']['list']} ON {$this->prefix}push_notification_stats (list_id)");
        $this->addSql("CREATE INDEX {$this->keys['push_notification_stats']['idx']['ip']} ON {$this->prefix}push_notification_stats (ip_id)");
        $this->addSql("CREATE INDEX {$this->prefix}stat_notification_search ON {$this->prefix}push_notification_stats (notification_id, lead_id)");
        $this->addSql("CREATE INDEX {$this->prefix}stat_notification_clicked_search ON {$this->prefix}push_notification_stats (is_clicked)");
        $this->addSql("CREATE INDEX {$this->prefix}stat_notification_hash_search ON {$this->prefix}push_notification_stats (tracking_hash)");
        $this->addSql("CREATE INDEX {$this->prefix}stat_notification_source_search ON {$this->prefix}push_notification_stats (source, source_id)");

        $this->addSql("COMMENT ON COLUMN {$this->prefix}push_notification_stats.date_sent IS '(DC2Type:datetime)'");
        $this->addSql("COMMENT ON COLUMN {$this->prefix}push_notification_stats.date_clicked IS '(DC2Type:datetime)'");
        $this->addSql("COMMENT ON COLUMN {$this->prefix}push_notification_stats.tokens IS '(DC2Type:array)'");
        $this->addSql("COMMENT ON COLUMN {$this->prefix}push_notification_stats.last_clicked IS '(DC2Type:datetime)'");
        $this->addSql("COMMENT ON COLUMN {$this->prefix}push_notification_stats.click_details IS '(DC2Type:array)'");

        $this->addSql("ALTER TABLE {$this->prefix}push_notifications ADD CONSTRAINT {$this->keys['push_notifications']['fk']['category']} FOREIGN KEY (category_id) REFERENCES {$this->prefix}categories (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE");
        $this->addSql("ALTER TABLE {$this->prefix}push_notification_list_xref ADD CONSTRAINT {$this->keys['push_notification_list_xref']['fk']['notification']} FOREIGN KEY (notification_id) REFERENCES {$this->prefix}push_notifications (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE");
        $this->addSql("ALTER TABLE {$this->prefix}push_notification_list_xref ADD CONSTRAINT {$this->keys['push_notification_list_xref']['fk']['leadlist']} FOREIGN KEY (leadlist_id) REFERENCES {$this->prefix}lead_lists (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE");
        $this->addSql("ALTER TABLE {$this->prefix}push_ids ADD CONSTRAINT {$this->keys['push_ids']['fk']['lead']} FOREIGN KEY (lead_id) REFERENCES {$this->prefix}leads (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE");
        $this->addSql("ALTER TABLE {$this->prefix}push_notification_stats ADD CONSTRAINT {$this->keys['push_notification_stats']['fk']['notification']} FOREIGN KEY (notification_id) REFERENCES {$this->prefix}push_notifications (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE");
        $this->addSql("ALTER TABLE {$this->prefix}push_notification_stats ADD CONSTRAINT {$this->keys['push_notification_stats']['fk']['lead']} FOREIGN KEY (lead_id) REFERENCES {$this->prefix}leads (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE");
        $this->addSql("ALTER TABLE {$this->prefix}push_notification_stats ADD CONSTRAINT {$this->keys['push_notification_stats']['fk']['list']} FOREIGN KEY (list_id) REFERENCES {$this->prefix}lead_lists (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE");
        $this->addSql("ALTER TABLE {$this->prefix}push_notification_stats ADD CONSTRAINT {$this->keys['push_notification_stats']['fk']['ip']} FOREIGN KEY (ip_id) REFERENCES {$this->prefix}ip_addresses (id) NOT DEFERRABLE INITIALLY IMMEDIATE");
    }
}