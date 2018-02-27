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
class Version20160926000001 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     */
    public function preUp(Schema $schema)
    {
        if ($schema->hasTable($this->prefix.'lead_companies_change_log')) {
            throw new SkipMigrationException('Schema includes this migration');
        }
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS {$this->prefix}lead_companies_change_log (
  id int(11) AUTO_INCREMENT NOT NULL,
  lead_id int(11) NOT NULL,
  company_id INT(11) NOT NULL,
  type tinytext NOT NULL,
  event_name varchar(255) NOT NULL,
  action_name varchar(255) NOT NULL,
  date_added DATETIME NOT NULL COMMENT '(DC2Type:datetime)',
  PRIMARY KEY(id),
  INDEX {$this->prefix}company_date_added (date_added)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;
SQL;
        $this->addSql($sql);

        $leadFk  = $this->generatePropertyName('lead_companies_change_log', 'fk', ['lead_id']);
        $leadIdx = $this->generatePropertyName('lead_companies_change_log', 'idx', ['lead_id']);
        $this->addSql("ALTER TABLE {$this->prefix}lead_companies_change_log ADD CONSTRAINT $leadFk FOREIGN KEY (lead_id) REFERENCES {$this->prefix}leads (id) ON DELETE CASCADE");
        $this->addSql("CREATE INDEX $leadIdx ON {$this->prefix}lead_companies_change_log (lead_id)");
    }
}
