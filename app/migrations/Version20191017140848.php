<?php

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20191017140848 extends AbstractMauticMigration
{
    /**
     * @throws SkipMigration
     * @throws SchemaException
     */
    public function preUp(Schema $schema): void
    {
        $smsStatsTable = $schema->getTable(MAUTIC_TABLE_PREFIX.'sms_message_stats');
        if ($smsStatsTable->hasColumn('is_failed') && $smsStatsTable->hasColumn('details')) {
            throw new SkipMigration('Schema includes this migration');
        }
    }

    /**
     * @throws SchemaException
     */
    public function up(Schema $schema): void
    {
        $smsStatsTable = $schema->getTable(MAUTIC_TABLE_PREFIX.'sms_message_stats');
        if (!$smsStatsTable->hasColumn('is_failed')) {
            $this->addSql('ALTER TABLE '.$this->prefix.'sms_message_stats ADD is_failed TINYINT(1) DEFAULT NULL');
            $this->addSql("UPDATE {$this->prefix}sms_message_stats SET is_failed = '0'");
            $this->addSql("CREATE INDEX {$this->prefix}stat_sms_failed_search ON {$this->prefix}sms_message_stats (is_failed)");
        }

        if (!$smsStatsTable->hasColumn('details')) {
            $this->addSql("ALTER TABLE {$this->prefix}sms_message_stats ADD details LONGTEXT NOT NULL COMMENT '(DC2Type:json_array)';");
        }
    }
}
