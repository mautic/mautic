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
 * Class Version20160726000000.
 */
class Version20160726000000 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     *
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema)
    {
        if ($schema->hasTable(MAUTIC_TABLE_PREFIX.'lead_frequencyrules')) {
            throw new SkipMigrationException('Schema includes this migration');
        }
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $leadIdx = $this->generatePropertyName('lead_frequencyrules', 'idx', ['lead_id']);
        $leadFk  = $this->generatePropertyName('lead_frequencyrules', 'fk', ['lead_id']);

        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `{$this->prefix}lead_frequencyrules` (
  `id` INT AUTO_INCREMENT NOT NULL,
  `lead_id` INT NOT NULL,
  `date_added` datetime NOT NULL COMMENT '(DC2Type:datetime)',
  `channel` varchar(255) NOT NULL,
  `frequency_time` varchar(25) NOT NULL,
  `frequency_number` smallint(6) NOT NULL,
   PRIMARY KEY (id),
   INDEX $leadIdx (lead_id),
   INDEX {$this->prefix}channel_frequency (channel)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;
SQL;
        $this->addSql($sql);

        $this->addSql("ALTER TABLE {$this->prefix}lead_frequencyrules ADD CONSTRAINT $leadFk FOREIGN KEY (lead_id) REFERENCES {$this->prefix}leads (id) ON DELETE CASCADE");
    }
}
