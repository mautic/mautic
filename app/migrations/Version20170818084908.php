<?php

/*
 * @package     Mautic
 * @copyright   2017 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Doctrine\DBAL\Migrations\SkipMigrationException;
use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

class Version20170818084908 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     *
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema)
    {
        $shouldRunMigration = !$schema->hasTable($this->prefix.'scoring_categories');

        if (!$shouldRunMigration) {
            throw new SkipMigrationException('Schema includes this migration (scoring categories)');
        }
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $keys                                                = [];
        $keys['scoring_company_values_company_xref']         = $this->generatePropertyName('scoring_company_values_company_xref', 'fk', ['company_id']);
        $keys['scoring_company_values_scoringcategory_xref'] = $this->generatePropertyName('scoring_company_values_scoringcategory_xref', 'fk', ['scoringcategory_id']);
        $keys['scoring_values_lead_xref']                    = $this->generatePropertyName('scoring_values_lead_xref', 'fk', ['lead_id']);
        $keys['scoring_values_scoringcategory_xref']         = $this->generatePropertyName('scoring_values_scoringcategory_xref', 'fk', ['scoringcategory_id']);
        $keys['campaign_events_scoringcategory_xref']        = $this->generatePropertyName('campaign_events_scoringcategory_xref', 'fk', ['scoringcategory_id']);
        $keys['points_scoringcategory_xref']                 = $this->generatePropertyName('points_scoringcategory_xref', 'fk', ['scoringcategory_id']);
        $keys['point_triggers_scoringcategory_xref']         = $this->generatePropertyName('point_triggers_scoringcategory_xref', 'fk', ['scoringcategory_id']);

        $this->addSql('CREATE TABLE `'.$this->prefix.'scoring_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `is_published` tinyint(1) NOT NULL,
  `date_added` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `checked_out` datetime DEFAULT NULL,
  `checked_out_by` int(11) DEFAULT NULL,
  `checked_out_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8_unicode_ci,
  `publish_up` datetime DEFAULT NULL,
  `publish_down` datetime DEFAULT NULL,
  `order_index` int(11) NOT NULL,
  `update_global_score` tinyint(1) NOT NULL,
  `global_score_modifier` double NOT NULL,
  `is_global_score` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci');
        $this->addSql('CREATE TABLE `'.$this->prefix.'scoring_company_values` (
  `company_id` int(11) NOT NULL,
  `scoringcategory_id` int(11) NOT NULL,
  `score` int(11) NOT NULL,
  PRIMARY KEY (`company_id`,`scoringcategory_id`),
  KEY `'.$keys['scoring_company_values_scoringcategory_xref'].'` (`company_id`),
  KEY `'.$keys['scoring_company_values_company_xref'].'` (`scoringcategory_id`),
  CONSTRAINT `'.$keys['scoring_company_values_scoringcategory_xref'].'` FOREIGN KEY (`scoringcategory_id`) REFERENCES `'.$this->prefix.'scoring_categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `'.$keys['scoring_company_values_company_xref'].'` FOREIGN KEY (`company_id`) REFERENCES `'.$this->prefix.'companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci');
        $this->addSql('CREATE TABLE `'.$this->prefix.'scoring_values` (
  `lead_id` int(11) NOT NULL,
  `scoringcategory_id` int(11) NOT NULL,
  `score` int(11) NOT NULL,
  PRIMARY KEY (`lead_id`,`scoringcategory_id`),
  KEY `'.$keys['scoring_values_lead_xref'].'` (`lead_id`),
  KEY `'.$keys['scoring_values_scoringcategory_xref'].'` (`scoringcategory_id`),
  CONSTRAINT `'.$keys['scoring_values_lead_xref'].'` FOREIGN KEY (`lead_id`) REFERENCES `'.$this->prefix.'leads` (`id`) ON DELETE CASCADE,
  CONSTRAINT `'.$keys['scoring_values_scoringcategory_xref'].'` FOREIGN KEY (`scoringcategory_id`) REFERENCES `'.$this->prefix.'scoring_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci');

        $this->addSql('ALTER TABLE `'.$this->prefix.'campaign_events` ADD COLUMN `scoringcategory_id` INT DEFAULT NULL');
        $this->addSql('ALTER TABLE `'.$this->prefix.'points` ADD COLUMN `scoringcategory_id` INT DEFAULT NULL');
        $this->addSql('ALTER TABLE `'.$this->prefix.'point_triggers` ADD COLUMN `scoringcategory_id` INT DEFAULT NULL');

        $this->addSql('ALTER TABLE `'.$this->prefix.'campaign_events` ADD CONSTRAINT '.$keys['campaign_events_scoringcategory_xref'].' FOREIGN KEY (`scoringcategory_id`) REFERENCES '.$this->prefix.'scoring_categories (`id`) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE `'.$this->prefix.'points` ADD CONSTRAINT '.$keys['points_scoringcategory_xref'].' FOREIGN KEY (`scoringcategory_id`) REFERENCES '.$this->prefix.'scoring_categories (`id`) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE `'.$this->prefix.'point_triggers` ADD CONSTRAINT '.$keys['point_triggers_scoringcategory_xref'].' FOREIGN KEY (`scoringcategory_id`) REFERENCES '.$this->prefix.'scoring_categories (`id`) ON DELETE SET NULL');

        $sql = <<<SQL
INSERT INTO `{$this->prefix}scoring_categories` (`id`,
  `is_published`,
  `date_added`,
  `created_by`,
  `created_by_user`,
  `date_modified`,
  `modified_by`,
  `modified_by_user`,
  `checked_out`,
  `checked_out_by`,
  `checked_out_by_user`,
  `name`,
  `description`,
  `publish_up`,
  `publish_down`,
  `order_index`,
  `update_global_score`,
  `global_score_modifier`,
  `is_global_score`) 
VALUES (1, 1, now(), 1, 'Admin', now(), 1, 'Admin', null, null, null, 'Global Scoring', '', null, null, 0, 1, 100, 1);
SQL;
        $this->addSql($sql);

        $this->addSql('UPDATE `'.$this->prefix.'points` set `scoringcategory_id`=1 where `scoringcategory_id` is null');
        $this->addSql('UPDATE `'.$this->prefix.'campaign_events` set `scoringcategory_id`=1 where `scoringcategory_id` is null');
        $this->addSql('UPDATE `'.$this->prefix.'point_triggers` set `scoringcategory_id`=1 where `scoringcategory_id` is null');
    }
}
