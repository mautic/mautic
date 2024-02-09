<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20240209104703 extends AbstractMauticMigration
{
    public function preUp(Schema $schema): void
    {
        $smsTable      = $schema->getTable(MAUTIC_TABLE_PREFIX.'sms_messages');
        if ($smsTable->hasColumn('properties')) {
            throw new SkipMigration('Schema includes this migration');
        }
    }

    /**
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function up(Schema $schema): void
    {
        $smsTable = $schema->getTable(MAUTIC_TABLE_PREFIX.'sms_messages');
        if (!$smsTable->hasColumn('properties')) {
            $this->addSql("ALTER TABLE {$this->prefix}sms_messages ADD properties LONGTEXT NOT NULL COMMENT '(DC2Type:json)'");
        }
    }
}
