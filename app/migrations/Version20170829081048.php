<?php

namespace Mautic\Migrations;

use Doctrine\DBAL\Migrations\SkipMigrationException;
use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170829081048 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     *
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema)
    {
        if ($schema->hasTable(MAUTIC_TABLE_PREFIX.'email_stat_replies')) {
            throw new SkipMigrationException('Schema includes this migration');
        }
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        list($idx, $fk) = $this->generateKeys('email_stat_replies', ['stat_id']);

        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS {$this->prefix}email_stat_replies (
    id CHAR(36) NOT NULL COMMENT '(DC2Type:guid)',
    stat_id INT NOT NULL,
    date_replied DATETIME NOT NULL COMMENT '(DC2Type:datetime)',
    message_id VARCHAR(255) NOT NULL,
    INDEX $idx (stat_id),
    INDEX {$this->prefix}email_replies (stat_id, message_id),
    INDEX {$this->prefix}date_email_replied (date_replied),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;
SQL;
        $this->addSql($sql);

        $this->addSql("ALTER TABLE {$this->prefix}email_stat_replies ADD CONSTRAINT $fk FOREIGN KEY (stat_id) REFERENCES {$this->prefix}email_stats (id) ON DELETE CASCADE;");
    }
}
