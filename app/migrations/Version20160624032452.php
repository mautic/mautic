<?php

namespace Mautic\Migrations;

use Doctrine\DBAL\Migrations\SkipMigrationException;
use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160624032452 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     *
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema)
    {
        if ($schema->hasTable(MAUTIC_TABLE_PREFIX.'dynamic_content_stats')) {
            throw new SkipMigrationException('Schema includes this migration');
        }
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $statDwcFK   = $this->generatePropertyName('dynamic_content_stats', 'fk', ['dynamic_content_id']);
        $statDwcIDX  = $this->generatePropertyName('dynamic_content_stats', 'idx', ['dynamic_content_id']);
        $statLeadFK  = $this->generatePropertyName('dynamic_content_stats', 'fk', ['lead_id']);
        $statLeadIDX = $this->generatePropertyName('dynamic_content_stats', 'idx', ['lead_id']);

        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS {$this->prefix}dynamic_content_stats (
	id INT AUTO_INCREMENT NOT NULL,
	dynamic_content_id INT DEFAULT NULL,
	lead_id INT DEFAULT NULL,
	date_sent DATETIME NOT NULL COMMENT '(DC2Type:datetime)',
	source VARCHAR(255) DEFAULT NULL,
	source_id INT DEFAULT NULL,
	tokens LONGTEXT DEFAULT NULL COMMENT '(DC2Type:array)',
	sent_count INT DEFAULT NULL,
	last_sent DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)',
	sent_details LONGTEXT DEFAULT NULL COMMENT '(DC2Type:array)',
	INDEX {$statDwcIDX} (dynamic_content_id),
	INDEX {$statLeadIDX} (lead_id),
	INDEX {$this->prefix}stat_dynamic_content_search (dynamic_content_id, lead_id),
	INDEX {$this->prefix}stat_dynamic_content_source_search (source, source_id),
	PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;
SQL;
        $this->addSql($sql);
        $this->addSql("ALTER TABLE {$this->prefix}dynamic_content_stats ADD CONSTRAINT {$statDwcFK} FOREIGN KEY (dynamic_content_id) REFERENCES {$this->prefix}dynamic_content (id) ON DELETE SET NULL;");
        $this->addSql("ALTER TABLE {$this->prefix}dynamic_content_stats ADD CONSTRAINT {$statLeadFK} FOREIGN KEY (lead_id) REFERENCES {$this->prefix}leads (id) ON DELETE SET NULL;");
    }
}
