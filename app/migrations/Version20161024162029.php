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
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161024162029 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     *
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema)
    {
        if ($schema->getTable($this->prefix.'lead_frequencyrules')->hasColumn('preferred_channel')) {
            throw new SkipMigrationException('Schema includes this migration');
        }
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql("ALTER TABLE {$this->prefix}lead_frequencyrules ADD preferred_channel TINYINT(1) NOT NULL DEFAULT 0;");
        $this->addSql("ALTER TABLE {$this->prefix}lead_frequencyrules ADD pause_from_date datetime DEFAULT NULL COMMENT '(DC2Type:datetime)';");
        $this->addSql("ALTER TABLE {$this->prefix}lead_frequencyrules ADD pause_to_date datetime DEFAULT NULL COMMENT '(DC2Type:datetime)';");

        $leadIdx     = $this->generatePropertyName('lead_categories', 'idx', ['lead_id']);
        $leadFk      = $this->generatePropertyName('lead_categories', 'fk', ['lead_id']);
        $categoryIdx = $this->generatePropertyName('lead_categories', 'idx', ['category_id']);
        $categoryFk  = $this->generatePropertyName('lead_categories', 'fk', ['category_id']);

        $sql = <<<SQL
CREATE TABLE {$this->prefix}lead_categories (
  id int(11) AUTO_INCREMENT NOT NULL,
  category_id int(11) NOT NULL,
  lead_id int(11) NOT NULL,
  date_added datetime NOT NULL,
  manually_removed tinyint(1) NOT NULL,
  manually_added tinyint(1) NOT NULL,
  PRIMARY KEY (id),
  INDEX {$leadIdx} (lead_id),
  INDEX {$categoryIdx} (category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SQL;
        $this->addSql($sql);

        $this->addSql("ALTER TABLE {$this->prefix}lead_categories ADD CONSTRAINT $leadFk FOREIGN KEY (lead_id) REFERENCES {$this->prefix}leads (id) ON DELETE CASCADE");
        $this->addSql("ALTER TABLE {$this->prefix}lead_categories ADD CONSTRAINT $categoryFk FOREIGN KEY (category_id) REFERENCES {$this->prefix}categories (id) ON DELETE CASCADE");
    }
}
