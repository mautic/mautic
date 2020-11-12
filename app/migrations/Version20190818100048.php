<?php

/*
 * @package     Mautic
 * @copyright   2020 Mautic Contributors. All rights reserved.
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
class Version20190818100048 extends AbstractMauticMigration
{
    public function preUp(Schema $schema): void
    {
        $smsTable      = $schema->getTable(MAUTIC_TABLE_PREFIX.'sms_messages');
        $smsStatsTable = $schema->getTable(MAUTIC_TABLE_PREFIX.'sms_message_stats');
        if ($smsStatsTable->hasColumn('is_delivered') && $smsStatsTable->hasColumn('is_read') && $smsStatsTable->hasColumn('is_failed') && $smsTable->hasColumn('delivered_count') && $smsTable->hasColumn('read_count') && $smsTable->hasColumn('failed_count') && $smsTable->hasColumn('properties')) {
            throw new SkipMigrationException('Schema includes this migration');
        }
    }

    /**
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function up(Schema $schema): void
    {
        $smsStatsTable = $schema->getTable(MAUTIC_TABLE_PREFIX.'sms_message_stats');
        if (!$smsStatsTable->hasColumn('is_delivered')) {
            $this->addSql('ALTER TABLE '.$this->prefix.'sms_message_stats ADD is_delivered TINYINT(1)  NOT NULL');
        }
        if (!$smsStatsTable->hasColumn('is_read')) {
            $this->addSql('ALTER TABLE '.$this->prefix.'sms_message_stats ADD is_read TINYINT(1) NOT NULL');
        }
        if (!$smsStatsTable->hasColumn('is_failed')) {
            $this->addSql('ALTER TABLE '.$this->prefix.'sms_message_stats ADD is_failed TINYINT(1) NULL');
        }

        $smsTable = $schema->getTable(MAUTIC_TABLE_PREFIX.'sms_messages');
        if (!$smsTable->hasColumn('delivered_count')) {
            $this->addSql('ALTER TABLE '.$this->prefix.'sms_messages ADD delivered_count INT NOT NULL');
        }
        if (!$smsTable->hasColumn('read_count')) {
            $this->addSql('ALTER TABLE '.$this->prefix.'sms_messages ADD read_count INT NOT NULL');
        }
        if (!$smsTable->hasColumn('failed_count')) {
            $this->addSql('ALTER TABLE '.$this->prefix.'sms_messages ADD failed_count INT NOT NULL');
        }
        if (!$smsTable->hasColumn('properties')) {
            $this->addSql("ALTER TABLE {$this->prefix}sms_messages ADD properties LONGTEXT NOT NULL COMMENT '(DC2Type:json_array)'");
        }
    }
}
