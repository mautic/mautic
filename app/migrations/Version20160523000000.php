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
use Mautic\LeadBundle\Entity\DoNotContact;

/**
 * Universal DNC Migration
 */
class Version20160523000000 extends AbstractMauticMigration
{
    private $leadIdIdx;
    private $leadIdFk;

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
    public function mysqlUp(Schema $schema)
    {
        $sql = <<<SQL
CREATE TABLE `{$this->prefix}lead_stages_change_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lead_id` int(11) NOT NULL,
  `type` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `event_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `action_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `date_added` datetime NOT NULL  COMMENT '(DC2Type:datetime)',
  INDEX {$this->generatePropertyName('lead_stages_change_log', 'idx', array('lead_id'))} (lead_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SQL;

        $this->addSql($sql);
        $this->addSql('ALTER TABLE ' . $this->prefix . 'lead_stages_change_log ADD CONSTRAINT ' . $this->generatePropertyName('lead_stages_change_log', 'fk', array('lead_id')) . ' FOREIGN KEY (lead_id) REFERENCES ' . $this->prefix . 'leads (id) ON DELETE CASCADE');

        $sql = <<<SQL
CREATE TABLE `{$this->prefix}stage_lead_action_log` (
  `stage_id` int(11) NOT NULL,
  `lead_id` int(11) NOT NULL,
  `ip_id` int(11) DEFAULT NULL,
  `date_fired` datetime NOT NULL,
  INDEX {$this->generatePropertyName('stage_lead_action_log', 'idx', array('lead_id'))} (lead_id),
  INDEX {$this->generatePropertyName('stage_lead_action_log', 'idx', array('stage_id'))} (stage_id),
  INDEX {$this->generatePropertyName('stage_lead_action_log', 'idx', array('ip_id'))} (ip_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SQL;
        $this->addSql($sql);

        $this->addSql('ALTER TABLE ' . $this->prefix . 'stage_lead_action_log ADD CONSTRAINT ' . $this->generatePropertyName('stage_lead_action_log', 'fk', array('lead_id')) . ' FOREIGN KEY (lead_id) REFERENCES ' . $this->prefix . 'leads (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'stage_lead_action_log ADD CONSTRAINT ' . $this->generatePropertyName('stage_lead_action_log', 'fk', array('stage_id')) . ' FOREIGN KEY (stage_id) REFERENCES ' . $this->prefix . 'stages (id)');
        $this->addSql('ALTER TABLE ' . $this->prefix . 'stage_lead_action_log ADD CONSTRAINT ' . $this->generatePropertyName('stage_lead_action_log', 'fk', array('ip_id')) . ' FOREIGN KEY (ip_id) REFERENCES ' . $this->prefix . 'ip_addresses (id)');


        $sql = <<<SQL
CREATE TABLE `{$this->prefix}stages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) DEFAULT NULL,
  `checked_out` datetime DEFAULT NULL,
  `checked_out_by` int(11) DEFAULT NULL,
  `checked_out_by_user` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_by_user` varchar(255) DEFAULT NULL,
  `description` longtext,
  `date_added` datetime NOT NULL COMMENT '(DC2Type:datetime)',
  `date_modified` datetime DEFAULT NULL COMMENT '(DC2Type:datetime)',
  `is_published` tinyint(1) DEFAULT NULL,  
  `modified_by` int(11) DEFAULT NULL,
  `modified_by_user` varchar(255) DEFAULT NULL,  
  `name` varchar(255) DEFAULT NULL,
  `properties` longtext,
  `publish_up` datetime DEFAULT NULL COMMENT '(DC2Type:datetime)',
  `publish_down` datetime DEFAULT NULL COMMENT '(DC2Type:datetime)',
  `type` varchar(50) DEFAULT NULL,
  `weight` int(11) DEFAULT NULL
  PRIMARY KEY (`id`),
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SQL;

        $this->addSql($sql);
    }

    /**
     * @param Schema $schema
     */
    public function postgresqlUp(Schema $schema)
    {
        $this->addSql("CREATE SEQUENCE {$this->prefix}lead_stages_change_log_id_seq INCREMENT BY 1 MINVALUE 1 START 1");

        $sql = <<<SQL
CREATE TABLE `{$this->prefix}lead_stages_change_log` (
  `id` INT NOT NULL,
  `lead_id` INT NOT NULL,
  `type` VARCHAR(50) NULL,
  `event_name` VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL,
  `action_name` VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL,
  `date_added` TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SQL;

        $this->addSql($sql);
        $this->addSql("CREATE INDEX {$this->leadIdIdx} ON {$this->prefix}lead_stages_change_log (lead_id)");

        $this->addSql("ALTER TABLE {$this->prefix}lead_stages_change_log ADD CONSTRAINT {$this->leadIdFk} FOREIGN KEY (lead_id) REFERENCES {$this->prefix}leads (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE");

        $this->addSql("CREATE SEQUENCE {$this->prefix}stage_lead_action_log INCREMENT BY 1 MINVALUE 1 START 1");
        $sql = <<<SQL
CREATE TABLE `{$this->prefix}stage_lead_action_log` (
  `stage_id` INT NOT NULL,
  `lead_id` INT NOT NULL,
  `ip_id` INT DEFAULT NULL,
  `date_fired` TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SQL;
        $this->addSql($sql);

        $this->addSql("CREATE INDEX {$this->leadIdIdx} ON {$this->prefix}stage_lead_action_log (lead_id)");
        $this->addSql("CREATE INDEX {$this->stagesIdIdx} ON {$this->prefix}stage_lead_action_log (stage_id)");
        $this->addSql("CREATE INDEX {$this->ipIdIdx} ON {$this->prefix}stage_lead_action_log (ip_id)");

        $this->addSql("ALTER TABLE {$this->prefix}stage_lead_action_log ADD CONSTRAINT {$this->leadIdFk} FOREIGN KEY (lead_id) REFERENCES {$this->prefix}leads (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE");
        $this->addSql("ALTER TABLE {$this->prefix}stage_lead_action_log ADD CONSTRAINT {$this->stagesIdFk} FOREIGN KEY (stage_id) REFERENCES {$this->prefix}stages (id)");
        $this->addSql("ALTER TABLE {$this->prefix}stage_lead_action_log ADD CONSTRAINT {$this->ipIdFk} FOREIGN KEY (ip_id) REFERENCES {$this->prefix}ip_addresses (id)");


        $this->addSql("CREATE SEQUENCE {$this->prefix}stages_id_seq INCREMENT BY 1 MINVALUE 1 START 1");

        $sql = <<<SQL
CREATE TABLE {$this->prefix}stages (
  id INT NOT NULL, 
  category_id INT DEFAULT NULL,
  checked_out TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
  checked_out_by INT DEFAULT NULL,
  checked_out_by_user VARCHAR(255) DEFAULT NULL,
  created_by INT DEFAULT NULL,
  created_by_user VARCHAR(255) DEFAULT NULL,
  description TEXT  DEFAULT NULL,
  date_added TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
  date_modified TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
  is_published SMALLINT DEFAULT NULL,  
  modified_by INT DEFAULT NULL,
  modified_by_user VARCHAR(255) DEFAULT NULL,  
  name VARCHAR(255) DEFAULT NULL,
  properties TEXT  DEFAULT NULL,,
  publish_up TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
  publish_down TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
  type VARCHAR(50) DEFAULT NULL,
  weight INT DEFAULT NULL
  PRIMARY KEY (`id`)
);
SQL;

        $this->addSql($sql);

    }
}
