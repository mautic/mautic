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
use Mautic\LeadBundle\Entity\UtmTag;

/**
 * UTM tags Migration
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

        $this->leadIdIdx = $this->generatePropertyName($this->prefix . 'lead_utmtags', 'idx', array('lead_id'));
        $this->leadIdFk  = $this->generatePropertyName($this->prefix . 'lead_utmtags', 'fk', array('lead_id'));
    }

    /**
     * @param Schema $schema
     */
    public function mysqlUp(Schema $schema)
    {

        $sql = <<<SQL
CREATE TABLE `{$this->prefix}lead_utmtags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date_added` datetime NOT NULL COMMENT '(DC2Type:datetime)',
  `lead_id` int(11) DEFAULT NULL,
  `query` LONGTEXT DEFAULT NULL COMMENT '(DC2Type:array)' DEFAULT NULL,
  `referer` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `remote_host` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `url` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `user_agent` longtext COLLATE utf8_unicode_ci DEFAULT NULL,
  `utm_campaign` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `utm_content` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `utm_medium` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `utm_source` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `utm_term` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `{$this->leadIdIdx}` (`lead_id`),
  CONSTRAINT `{$this->leadIdFk}` FOREIGN KEY (`lead_id`) REFERENCES `{$this->prefix}leads` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SQL;

        $this->addSql($sql);
    }

    /**
     * @param Schema $schema
     */
    public function postgresqlUp(Schema $schema)
    {
        $this->addSql("CREATE SEQUENCE {$this->prefix}lead_utmtags_id_seq INCREMENT BY 1 MINVALUE 1 START 1");

        $sql = <<<SQL
CREATE TABLE {$this->prefix}lead_utmtags (
  id INT NOT NULL, 
  date_added TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, 
  lead_id INT DEFAULT NULL, 
  query TEXT DEFAULT NULL,
  referer VARCHAR(255) DEFAULT NULL, 
  remote_host VARCHAR(255) DEFAULT NULL, 
  url VARCHAR(255) DEFAULT NULL, 
  user_agent TEXT DEFAULT NULL, 
  utm_campaign VARCHAR(255) DEFAULT NULL,
  utm_content VARCHAR(255) DEFAULT NULL,
  utm_medium VARCHAR(255) DEFAULT NULL,
  utm_source VARCHAR(255) DEFAULT NULL,
  utm_term VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY(id)
);
SQL;

        $this->addSql($sql);

        $this->addSql("CREATE INDEX {$this->leadIdIdx} ON {$this->prefix}lead_utmtags (lead_id)");
        $this->addSql("COMMENT ON COLUMN {$this->prefix}lead_utmtags.date_added IS '(DC2Type:datetime)'");
        $this->addSql("ALTER TABLE {$this->prefix}lead_utmtags ADD CONSTRAINT {$this->leadIdFk} FOREIGN KEY (lead_id) REFERENCES {$this->prefix}leads (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE");
    }

}
