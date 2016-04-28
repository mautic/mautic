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
 * Universal DNC Migration
 */
class Version20160426000000 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     *
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema)
    {
        if ($schema->hasTable($this->prefix.'lead_donotcontact')) {
            throw new SkipMigrationException('Schema includes this migration');
        }
    }

    /**
     * @param Schema $schema
     */
    public function mysqlUp(Schema $schema)
    {
        $leadIdIdx = $this->generatePropertyName($this->prefix . 'lead_donotcontact', 'idx', array('lead_id'));
        $leadIdFk = $this->generatePropertyName($this->prefix . 'lead_donotcontact', 'fk', array('lead_id'));

        $sql = <<<SQL
CREATE TABLE `{$this->prefix}lead_donotcontact` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lead_id` int(11) DEFAULT NULL,
  `channel` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `channel_id` int(11) DEFAULT NULL,
  `date_added` datetime NOT NULL COMMENT '(DC2Type:datetime)',
  `reason` tinyint(1) NOT NULL DEFAULT '0',
  `comments` longtext COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `{$leadIdIdx}` (`lead_id`),
  CONSTRAINT `{$leadIdFk}` FOREIGN KEY (`lead_id`) REFERENCES `{$this->prefix}leads` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SQL;

        $this->addSql($sql);
    }

    /**
     * @param Schema $schema
     */
    public function postgresqlUp(Schema $schema)
    {
        
    }
}
