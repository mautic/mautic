<?php

namespace Mautic\Migrations;

use Doctrine\DBAL\Migrations\SkipMigrationException;
use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160617000318 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     *
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema)
    {
        if ($schema->hasTable(MAUTIC_TABLE_PREFIX.'dynamic_content')) {
            throw new SkipMigrationException('Schema includes this migration');
        }
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $dwcCatFK  = $this->generatePropertyName('dynamic_content', 'fk', ['category_id']);
        $dwcCatIDX = $this->generatePropertyName('dynamic_content', 'idx', ['category_id']);
        $dwcVarFK  = $this->generatePropertyName('dynamic_content', 'fk', ['variant_parent_id']);
        $dwcVarIDX = $this->generatePropertyName('dynamic_content', 'idx', ['variant_parent_id']);

        $dwcSql = <<<SQL
CREATE TABLE IF NOT EXISTS {$this->prefix}dynamic_content (
  id INT AUTO_INCREMENT NOT NULL,
  category_id INT DEFAULT NULL,
  variant_parent_id INT DEFAULT NULL,
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
  publish_up DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)',
  publish_down DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)',
  sent_count INT NOT NULL,
  content LONGTEXT DEFAULT NULL,
  lang VARCHAR(255) NOT NULL,
  INDEX {$dwcCatIDX} (category_id),
  INDEX {$dwcVarIDX} (variant_parent_id),
  PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;
SQL;
        $this->addSql($dwcSql);
        $this->addSql("ALTER TABLE {$this->prefix}dynamic_content ADD CONSTRAINT {$dwcCatFK} FOREIGN KEY (category_id) REFERENCES {$this->prefix}categories (id) ON DELETE SET NULL;");
        $this->addSql("ALTER TABLE {$this->prefix}dynamic_content ADD CONSTRAINT {$dwcVarFK} FOREIGN KEY (variant_parent_id) REFERENCES {$this->prefix}dynamic_content (id);");

        $dwc2LeadFK  = $this->generatePropertyName('dynamic_content_lead_data', 'fk', ['lead_id']);
        $dwc2LeadIDX = $this->generatePropertyName('dynamic_content_lead_data', 'idx', ['lead_id']);
        $dwc2DwcFK   = $this->generatePropertyName('dynamic_content_lead_data', 'fk', ['dynamic_content_id']);
        $dwc2DwcIDX  = $this->generatePropertyName('dynamic_content_lead_data', 'idx', ['dynamic_content_id']);

        $dwcSql2 = <<<SQL
CREATE TABLE IF NOT EXISTS {$this->prefix}dynamic_content_lead_data (
  id INT AUTO_INCREMENT NOT NULL,
  lead_id INT NOT NULL,
  dynamic_content_id INT NOT NULL,
  slot VARCHAR(255) NOT NULL,
  date_added DATETIME DEFAULT NULL,
  INDEX {$dwc2LeadIDX} (lead_id),
  INDEX {$dwc2DwcIDX} (dynamic_content_id),
  PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;
SQL;
        $this->addSql($dwcSql2);
        $this->addSql("ALTER TABLE {$this->prefix}dynamic_content_lead_data ADD CONSTRAINT {$dwc2LeadFK} FOREIGN KEY (lead_id) REFERENCES {$this->prefix}leads (id) ON DELETE CASCADE;");
        $this->addSql("ALTER TABLE {$this->prefix}dynamic_content_lead_data ADD CONSTRAINT {$dwc2DwcFK} FOREIGN KEY (dynamic_content_id) REFERENCES {$this->prefix}dynamic_content (id) ON DELETE CASCADE;");
    }
}
