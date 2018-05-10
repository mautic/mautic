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
class Version20160919204648 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     *
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema)
    {
        if ($schema->hasTable($this->prefix.'focus')) {
            throw new SkipMigrationException('Schema includes this migration');
        }
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $categoryIdx = $this->generatePropertyName('focus', 'idx', ['category_id']);
        $sql         = <<<SQL
CREATE TABLE IF NOT EXISTS {$this->prefix}focus (
  id INT AUTO_INCREMENT NOT NULL,
  category_id INT DEFAULT NULL,
  is_published TINYINT(1) NOT NULL,
  date_added DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)',
  created_by INT DEFAULT NULL,
  created_by_user VARCHAR(255) DEFAULT NULL,
  date_modified DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)',
  modified_by INT DEFAULT NULL,
  modified_by_user VARCHAR(255) DEFAULT NULL,
  checked_out DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)',
  checked_out_by INT DEFAULT NULL,
  checked_out_by_user VARCHAR(255) DEFAULT NULL,
  name VARCHAR(255) NOT NULL,
  description LONGTEXT DEFAULT NULL,
  focus_type VARCHAR(255) NOT NULL,
  style VARCHAR(255) NOT NULL,
  website VARCHAR(255) DEFAULT NULL,
  publish_up DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)',
  publish_down DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)',
  properties LONGTEXT DEFAULT NULL COMMENT '(DC2Type:array)',
  form_id INT DEFAULT NULL,
  cache LONGTEXT DEFAULT NULL,
  INDEX $categoryIdx (category_id),
  INDEX {$this->prefix}focus_type (focus_type),
  INDEX {$this->prefix}focus_style (style),
  INDEX {$this->prefix}focus_form (form_id),
  PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB
SQL;
        $this->addSql($sql);

        $focusIdx = $this->generatePropertyName('focus_stats', 'idx', ['focus_id']);
        $sql      = <<<SQL
CREATE TABLE IF NOT EXISTS {$this->prefix}focus_stats (
  id INT AUTO_INCREMENT NOT NULL,
  focus_id INT NOT NULL,
  type VARCHAR(255) NOT NULL,
  type_id INT DEFAULT NULL,
  date_added DATETIME NOT NULL COMMENT '(DC2Type:datetime)',
  INDEX $focusIdx (focus_id),
  INDEX {$this->prefix}focus_type (type),
  INDEX {$this->prefix}focus_type_id (type, type_id),
  INDEX {$this->prefix}focus_date_added (date_added),
  PRIMARY KEY(id)
)
    DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;
SQL;
        $this->addSql($sql);

        $categoryFk = $this->generatePropertyName('focus', 'fk', ['category_id']);
        $focusFk    = $this->generatePropertyName('focus_stats', 'fk', ['focus_id']);
        $this->addSql("ALTER TABLE {$this->prefix}focus ADD CONSTRAINT $categoryFk FOREIGN KEY (category_id) REFERENCES {$this->prefix}categories (id) ON DELETE SET NULL");
        $this->addSql("ALTER TABLE {$this->prefix}focus_stats ADD CONSTRAINT $focusFk FOREIGN KEY (focus_id) REFERENCES {$this->prefix}focus (id) ON DELETE CASCADE");
    }
}
