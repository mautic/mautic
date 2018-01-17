<?php

namespace Mautic\Migrations;

use Doctrine\DBAL\Migrations\SkipMigrationException;
use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160725161822 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     *
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema)
    {
        if ($schema->hasTable(MAUTIC_TABLE_PREFIX.'video_hits')) {
            throw new SkipMigrationException('Schema includes this migration');
        }
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $leadIDX = $this->generatePropertyName('video_hits', 'idx', ['lead_id']);
        $leadFK  = $this->generatePropertyName('video_hits', 'fk', ['lead_id']);
        $ipIDX   = $this->generatePropertyName('video_hits', 'idx', ['ip_id']);
        $ipFK    = $this->generatePropertyName('video_hits', 'fk', ['ip_id']);

        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS {$this->prefix}video_hits (
	id INT AUTO_INCREMENT NOT NULL,
	lead_id INT DEFAULT NULL,
	ip_id INT NOT NULL,
	date_hit DATETIME NOT NULL COMMENT '(DC2Type:datetime)',
	date_left DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)',
	country VARCHAR(255) DEFAULT NULL,
	region VARCHAR(255) DEFAULT NULL,
	city VARCHAR(255) DEFAULT NULL,
	isp VARCHAR(255) DEFAULT NULL,
	organization VARCHAR(255) DEFAULT NULL,
	code INT NOT NULL, referer LONGTEXT DEFAULT NULL,
	url LONGTEXT DEFAULT NULL,
	user_agent LONGTEXT DEFAULT NULL,
	remote_host VARCHAR(255) DEFAULT NULL,
	page_language VARCHAR(255) DEFAULT NULL,
	browser_languages LONGTEXT DEFAULT NULL COMMENT '(DC2Type:array)',
	channel VARCHAR(255) DEFAULT NULL,
	channel_id INT DEFAULT NULL,
	query LONGTEXT DEFAULT NULL COMMENT '(DC2Type:array)',
	time_watched INT DEFAULT NULL,
	duration INT DEFAULT NULL,
	guid VARCHAR(255) NOT NULL,
	INDEX {$leadIDX} (lead_id),
	INDEX {$ipIDX} (ip_id),
	INDEX {$this->prefix}video_date_hit (date_hit),
	PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;
SQL;
        $this->addSql($sql);
        $this->addSql("ALTER TABLE {$this->prefix}video_hits ADD CONSTRAINT {$leadFK} FOREIGN KEY (lead_id) REFERENCES {$this->prefix}leads (id) ON DELETE SET NULL;");
        $this->addSql("ALTER TABLE {$this->prefix}video_hits ADD CONSTRAINT {$ipFK} FOREIGN KEY (ip_id) REFERENCES {$this->prefix}ip_addresses (id);");

        $this->addSql("CREATE INDEX {$this->prefix}video_channel_search ON {$this->prefix}video_hits (channel, channel_id)");
        $this->addSql("CREATE INDEX {$this->prefix}video_guid_lead_search ON {$this->prefix}video_hits (guid, lead_id)");
    }
}
