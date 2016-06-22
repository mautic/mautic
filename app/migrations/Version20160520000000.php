<?php
/**
 * @package     Mautic
 * @copyright   2015 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Migrations\SkipMigrationException;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

/**
 * MauticSocialBundle schema
 *
 * Class Version20160520000000
 */
class Version20160520000000 extends AbstractMauticMigration
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
        // Test to see if this migration has already been applied
        if ($schema->hasTable($this->prefix.'monitoring_leads')) {

            throw new SkipMigrationException('Schema includes this migration');
        }


        $this->keys = array(
            'monitoring_leads'   => array(
                'idx' => array(
                    'monitor' => $this->generatePropertyName('monitoring_leads', 'idx', array('monitor_id')),
                    'lead'    => $this->generatePropertyName('monitoring_leads', 'idx', array('lead_id')),
                ),
                'fk'  => array(
                    'monitor' => $this->generatePropertyName('monitoring_leads', 'fk', array('monitor_id')),
                    'lead'    => $this->generatePropertyName('monitoring_leads', 'fk', array('lead_id')),
                )
            ),
            'monitoring'         => array(
                'idx' => array(
                    'category' => $this->generatePropertyName('monitoring', 'idx', array('category_id'))
                ),
                'fk'  => array(
                    'category' => $this->generatePropertyName('monitoring', 'fk', array('category_id')),
                )
            ),
            'monitor_post_count' => array(
                'idx' => array(
                    'monitor' => $this->generatePropertyName('monitor_post_count', 'idx', array('monitor_id'))
                ),
                'fk'  => array(
                    'monitor' => $this->generatePropertyName('monitor_post_count', 'fk', array('monitor_id'))
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
CREATE TABLE {$this->prefix}monitoring_leads (
      monitor_id INT NOT NULL, 
      lead_id INT NOT NULL, 
      date_added DATETIME NOT NULL, 
      INDEX {$this->keys['monitoring_leads']['idx']['monitor']} (monitor_id), 
      INDEX {$this->keys['monitoring_leads']['idx']['lead']} (lead_id), 
      PRIMARY KEY(monitor_id, lead_id)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB
SQL;
        $this->addSql($sql);


        $sql = <<<SQL
CREATE TABLE {$this->prefix}monitoring (
      id INT AUTO_INCREMENT NOT NULL, 
      category_id INT DEFAULT NULL, 
      is_published TINYINT(1) NOT NULL, 
      date_added DATETIME DEFAULT NULL, 
      created_by INT DEFAULT NULL, 
      created_by_user VARCHAR(255) DEFAULT NULL, 
      date_modified DATETIME DEFAULT NULL, 
      modified_by INT DEFAULT NULL, 
      modified_by_user VARCHAR(255) DEFAULT NULL, 
      checked_out DATETIME DEFAULT NULL, 
      checked_out_by INT DEFAULT NULL, 
      checked_out_by_user VARCHAR(255) DEFAULT NULL, 
      description LONGTEXT DEFAULT NULL, 
      lists LONGTEXT DEFAULT NULL COMMENT '(DC2Type:array)', 
      network_type VARCHAR(255) DEFAULT NULL, 
      revision INT NOT NULL, 
      stats LONGTEXT DEFAULT NULL COMMENT '(DC2Type:array)', 
      title VARCHAR(255) NOT NULL, 
      properties LONGTEXT DEFAULT NULL COMMENT '(DC2Type:array)', 
      publish_down DATETIME DEFAULT NULL, 
      publish_up DATETIME DEFAULT NULL, 
      INDEX {$this->keys['monitoring']['idx']['category']} (category_id), 
      PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB
SQL;
        $this->addSql($sql);

        $sql = <<<SQL
CREATE TABLE {$this->prefix}monitor_post_count (
    id INT AUTO_INCREMENT NOT NULL, 
    monitor_id INT DEFAULT NULL, 
    post_date DATE NOT NULL, 
    post_count INT NOT NULL, 
    INDEX {$this->keys['monitor_post_count']['idx']['monitor']} (monitor_id), 
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB
SQL;
        $this->addSql($sql);

        $this->addSql("ALTER TABLE {$this->prefix}monitoring_leads ADD CONSTRAINT {$this->keys['monitoring_leads']['fk']['monitor']} FOREIGN KEY (monitor_id) REFERENCES {$this->prefix}monitoring (id) ON DELETE CASCADE");
        $this->addSql("ALTER TABLE {$this->prefix}monitoring_leads ADD CONSTRAINT {$this->keys['monitoring_leads']['fk']['lead']} FOREIGN KEY (lead_id) REFERENCES {$this->prefix}leads (id) ON DELETE CASCADE");
        $this->addSql("ALTER TABLE {$this->prefix}monitoring ADD CONSTRAINT {$this->keys['monitoring']['fk']['category']} FOREIGN KEY (category_id) REFERENCES {$this->prefix}categories (id) ON DELETE SET NULL");
        $this->addSql("ALTER TABLE {$this->prefix}monitor_post_count ADD CONSTRAINT {$this->keys['monitor_post_count']['fk']['monitor']} FOREIGN KEY (monitor_id) REFERENCES {$this->prefix}monitoring (id) ON DELETE CASCADE");
    }

    /**
     * @param Schema $schema
     */
    public function postgresqlUp(Schema $schema)
    {
        $this->addSql("CREATE SEQUENCE {$this->prefix}monitoring_id_seq INCREMENT BY 1 MINVALUE 1 START 1");
        $this->addSql("CREATE SEQUENCE {$this->prefix}monitor_post_count_id_seq INCREMENT BY 1 MINVALUE 1 START 1");

        $sql = <<<SQL
CREATE TABLE {$this->prefix}monitoring_leads (
    monitor_id INT NOT NULL, 
    lead_id INT NOT NULL, 
    date_added TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, 
    PRIMARY KEY(monitor_id, lead_id)
);
SQL;
        $this->addSql($sql);
        $this->addSql("CREATE INDEX {$this->keys['monitoring_leads']['idx']['monitor']} ON {$this->prefix}monitoring_leads (monitor_id)");
        $this->addSql("CREATE INDEX {$this->keys['monitoring_leads']['idx']['lead']} ON {$this->prefix}monitoring_leads (lead_id)");
        $this->addSql("COMMENT ON COLUMN {$this->prefix}monitoring_leads.date_added IS '(DC2Type:datetime)'");

        $sql = <<<SQL
CREATE TABLE {$this->prefix}monitoring (
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
    title VARCHAR(255) NOT NULL, 
    description TEXT DEFAULT NULL, 
    lists TEXT DEFAULT NULL, 
    network_type VARCHAR(255) DEFAULT NULL, 
    revision INT NOT NULL, 
    stats TEXT DEFAULT NULL, 
    properties TEXT DEFAULT NULL, 
    publish_up TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, 
    publish_down TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, 
    PRIMARY KEY(id)
);
SQL;
        $this->addSql($sql);
        $this->addSql("CREATE INDEX {$this->keys['monitoring']['idx']['category']} ON {$this->prefix}monitoring (category_id)");
        $this->addSql("COMMENT ON COLUMN {$this->prefix}monitoring.date_added IS '(DC2Type:datetime)'");
        $this->addSql("COMMENT ON COLUMN {$this->prefix}monitoring.date_modified IS '(DC2Type:datetime)'");
        $this->addSql("COMMENT ON COLUMN {$this->prefix}monitoring.checked_out IS '(DC2Type:datetime)'");
        $this->addSql("COMMENT ON COLUMN {$this->prefix}monitoring.lists IS '(DC2Type:array)'");
        $this->addSql("COMMENT ON COLUMN {$this->prefix}monitoring.stats IS '(DC2Type:array)'");
        $this->addSql("COMMENT ON COLUMN {$this->prefix}monitoring.properties IS '(DC2Type:array)'");
        $this->addSql("COMMENT ON COLUMN {$this->prefix}monitoring.publish_up IS '(DC2Type:datetime)'");
        $this->addSql("COMMENT ON COLUMN {$this->prefix}monitoring.publish_down IS '(DC2Type:datetime)'");

        $sql = <<<SQL
CREATE TABLE {$this->prefix}monitor_post_count (
    id INT NOT NULL, 
    monitor_id INT DEFAULT NULL, 
    post_date DATE NOT NULL, 
    post_count INT NOT NULL, 
    PRIMARY KEY(id)
);
SQL;
        $this->addSql($sql);
        $this->addSql("CREATE INDEX {$this->keys['monitor_post_count']['idx']['monitor']} ON {$this->prefix}monitor_post_count (monitor_id)");

        $this->addSql("ALTER TABLE {$this->prefix}monitoring_leads ADD CONSTRAINT {$this->keys['monitoring_leads']['fk']['monitor']} FOREIGN KEY (monitor_id) REFERENCES {$this->prefix}monitoring (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE");
        $this->addSql("ALTER TABLE {$this->prefix}monitoring_leads ADD CONSTRAINT {$this->keys['monitoring_leads']['fk']['lead']} FOREIGN KEY (lead_id) REFERENCES {$this->prefix}leads (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE");
        $this->addSql("ALTER TABLE {$this->prefix}monitoring ADD CONSTRAINT {$this->keys['monitoring']['fk']['category']} FOREIGN KEY (category_id) REFERENCES {$this->prefix}categories (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE");
        $this->addSql("ALTER TABLE {$this->prefix}monitor_post_count ADD CONSTRAINT {$this->keys['monitor_post_count']['fk']['monitor']} FOREIGN KEY (monitor_id) REFERENCES {$this->prefix}monitoring (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE");
    }
}