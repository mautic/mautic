<?php

/*
 * @package     Mautic
 * @copyright   2017 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Doctrine\DBAL\Migrations\SkipMigrationException;
use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170609111615 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     *
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema)
    {
        $table = $schema->getTable($this->prefix.'focus_campaign');
        if ($table->hasColumn('leadeventlog_id') && $table->getColumn('leadeventlog_id')->getNotnull() === false) {
            throw new SkipMigrationException('Schema includes this migration');
        }
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE '.$this->prefix.'focus_campaign ADD leadeventlog_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE '.$this->prefix.'focus_campaign ADD CONSTRAINT FK_963C8FF26A94EBD2 FOREIGN KEY (leadeventlog_id) REFERENCES campaign_lead_event_log (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_963C8FF26A94EBD2 ON '.$this->prefix.'focus_campaign (leadeventlog_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE '.$this->prefix.'focus_campaign DROP FOREIGN KEY FK_963C8FF26A94EBD2');
        $this->addSql('DROP INDEX IDX_963C8FF26A94EBD2 ON '.$this->prefix.'focus_campaign');
        $this->addSql('ALTER TABLE '.$this->prefix.'focus_campaign DROP leadeventlog_id');
    }
}
