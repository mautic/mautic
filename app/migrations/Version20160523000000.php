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
 * Universal DNC Migration.
 */
class Version20160523000000 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     *
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema)
    {
        if ($schema->hasTable($this->prefix.'stages')) {
            throw new SkipMigrationException('Schema includes this migration');
        }
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `{$this->prefix}stages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) DEFAULT NULL,
  `checked_out` datetime DEFAULT NULL,
  `checked_out_by` int(11) DEFAULT NULL,
  `checked_out_by_user` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_by_user` varchar(255) DEFAULT NULL,
  `description` longtext,
  `date_added` datetime NULL COMMENT '(DC2Type:datetime)',
  `date_modified` datetime DEFAULT NULL COMMENT '(DC2Type:datetime)',
  `is_published` tinyint(1) NOT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_by_user` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `publish_up` datetime DEFAULT NULL COMMENT '(DC2Type:datetime)',
  `publish_down` datetime DEFAULT NULL COMMENT '(DC2Type:datetime)',
  `weight` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SQL;

        $this->addSql($sql);

        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `{$this->prefix}lead_stages_change_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lead_id` int(11) NOT NULL,
  `event_name` varchar(255) NOT NULL,
  `action_name` varchar(255) NOT NULL,
  `date_added` datetime NOT NULL  COMMENT '(DC2Type:datetime)',
  PRIMARY KEY (`id`),
  INDEX {$this->generatePropertyName('lead_stages_change_log', 'idx', ['lead_id'])} (lead_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SQL;

        $this->addSql($sql);
        $this->addSql('ALTER TABLE '.$this->prefix.'lead_stages_change_log ADD CONSTRAINT '.$this->generatePropertyName('lead_stages_change_log', 'fk', ['lead_id']).' FOREIGN KEY (lead_id) REFERENCES '.$this->prefix.'leads (id) ON DELETE CASCADE');

        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `{$this->prefix}stage_lead_action_log` (
  `stage_id` int(11) NOT NULL,
  `lead_id` int(11) NOT NULL,
  `ip_id` int(11) DEFAULT NULL,
  `date_fired` datetime NOT NULL,
  PRIMARY KEY (`stage_id`, `lead_id`),
  INDEX {$this->generatePropertyName('stage_lead_action_log', 'idx', ['lead_id'])} (lead_id),
  INDEX {$this->generatePropertyName('stage_lead_action_log', 'idx', ['stage_id'])} (stage_id),
  INDEX {$this->generatePropertyName('stage_lead_action_log', 'idx', ['ip_id'])} (ip_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SQL;
        $this->addSql($sql);

        $this->addSql('ALTER TABLE '.$this->prefix.'stage_lead_action_log ADD CONSTRAINT '.$this->generatePropertyName('stage_lead_action_log', 'fk', ['lead_id']).' FOREIGN KEY (lead_id) REFERENCES '.$this->prefix.'leads (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE '.$this->prefix.'stage_lead_action_log ADD CONSTRAINT '.$this->generatePropertyName('stage_lead_action_log', 'fk', ['stage_id']).' FOREIGN KEY (stage_id) REFERENCES '.$this->prefix.'stages (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE '.$this->prefix.'stage_lead_action_log ADD CONSTRAINT '.$this->generatePropertyName('stage_lead_action_log', 'fk', ['ip_id']).' FOREIGN KEY (ip_id) REFERENCES '.$this->prefix.'ip_addresses (id)');
        $this->addSql('ALTER TABLE '.$this->prefix.'leads ADD COLUMN stage_id INT DEFAULT NULL');
    }
}
