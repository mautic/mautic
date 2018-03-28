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
 * MauticSocialBundle schema.
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

        $this->keys = [
            'monitoring_leads' => [
                'idx' => [
                    'monitor' => $this->generatePropertyName('monitoring_leads', 'idx', ['monitor_id']),
                    'lead'    => $this->generatePropertyName('monitoring_leads', 'idx', ['lead_id']),
                ],
                'fk' => [
                    'monitor' => $this->generatePropertyName('monitoring_leads', 'fk', ['monitor_id']),
                    'lead'    => $this->generatePropertyName('monitoring_leads', 'fk', ['lead_id']),
                ],
            ],
            'monitoring' => [
                'idx' => [
                    'category' => $this->generatePropertyName('monitoring', 'idx', ['category_id']),
                ],
                'fk' => [
                    'category' => $this->generatePropertyName('monitoring', 'fk', ['category_id']),
                ],
            ],
            'monitor_post_count' => [
                'idx' => [
                    'monitor' => $this->generatePropertyName('monitor_post_count', 'idx', ['monitor_id']),
                ],
                'fk' => [
                    'monitor' => $this->generatePropertyName('monitor_post_count', 'fk', ['monitor_id']),
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
CREATE TABLE IF NOT EXISTS {$this->prefix}monitoring_leads (
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
CREATE TABLE IF NOT EXISTS {$this->prefix}monitoring (
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
CREATE TABLE IF NOT EXISTS {$this->prefix}monitor_post_count (
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
}
