<?php

/*
 * @package     Mautic
 * @copyright   2019 Mautic Contributors. All rights reserved.
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
class Version20190818101448 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     *
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema)
    {
        $smsTable = $schema->getTable(MAUTIC_TABLE_PREFIX.'sms_messages');
        if ($smsTable->hasColumn('delivered_count') && $smsTable->hasColumn('read_count') && $smsTable->hasColumn('failed_count')) {
            throw new SkipMigrationException('Schema includes this migration');
        }
    }

    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function up(Schema $schema)
    {
        $smsTable = $schema->getTable(MAUTIC_TABLE_PREFIX.'sms_messages');
        if (!$smsTable->hasColumn('delivered_count')) {
            $this->addSql('ALTER TABLE '.$this->prefix.'sms_messages ADD delivered_count INT DEFAULT NULL');
        }
        if (!$smsTable->hasColumn('read_count')) {
            $this->addSql('ALTER TABLE '.$this->prefix.'sms_messages ADD read_count INT DEFAULT NULL');
        }
        if (!$smsTable->hasColumn('failed_count')) {
            $this->addSql('ALTER TABLE '.$this->prefix.'sms_messages ADD failed_count INT DEFAULT NULL');
        }
    }
}
