<?php

namespace Mautic\Migrations;

use Doctrine\DBAL\Migrations\SkipMigrationException;
use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170322083012 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     *
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema)
    {
        if ($schema->hasTable($this->prefix.'campaign_event_daily_send_log')) {
            throw new SkipMigrationException('Schema includes this migration');
        }
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE '.$this->prefix.'campaign_event_daily_send_log (id INT AUTO_INCREMENT NOT NULL, event_id INT NOT NULL, sent_count INT NOT NULL, date DATE DEFAULT NULL, INDEX '.$this->generatePropertyName('campaign_event_daily_send_log', 'idx', ['event_id']).' (event_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE '.$this->prefix.'campaign_event_daily_send_log ADD CONSTRAINT '.$this->generatePropertyName('campaign_event_daily_send_log', 'fk', ['event_id']).' FOREIGN KEY (event_id) REFERENCES '.$this->prefix.'campaign_events (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE '.$this->prefix.'campaign_lead_event_log ADD is_queued TINYINT(1) NOT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE '.$this->prefix.'campaign_event_daily_send_log');
        $this->addSql('ALTER TABLE '.$this->prefix.'campaign_lead_event_log DROP is_queued');
    }
}
