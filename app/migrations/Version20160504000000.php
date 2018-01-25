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
 * UTM tags Migration.
 */
class Version20160504000000 extends AbstractMauticMigration
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
        if ($schema->hasTable($this->prefix.'lead_utmtags')) {
            throw new SkipMigrationException('Schema includes this migration');
        }

        $this->leadIdIdx = $this->generatePropertyName('lead_utmtags', 'idx', ['lead_id']);
        $this->leadIdFk  = $this->generatePropertyName('lead_utmtags', 'fk', ['lead_id']);
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `{$this->prefix}lead_utmtags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date_added` datetime NOT NULL COMMENT '(DC2Type:datetime)',
  `lead_id` int(11) NOT NULL,
  `query` LONGTEXT DEFAULT NULL COMMENT '(DC2Type:array)' DEFAULT NULL,
  `referer` varchar(255) DEFAULT NULL,
  `remote_host` varchar(255) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `user_agent` longtext DEFAULT NULL,
  `utm_campaign` varchar(255) DEFAULT NULL,
  `utm_content` varchar(255) DEFAULT NULL,
  `utm_medium` varchar(255) DEFAULT NULL,
  `utm_source` varchar(255) DEFAULT NULL,
  `utm_term` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `{$this->leadIdIdx}` (`lead_id`),
  CONSTRAINT `{$this->leadIdFk}` FOREIGN KEY (`lead_id`) REFERENCES `{$this->prefix}leads` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SQL;

        $this->addSql($sql);
    }
}
