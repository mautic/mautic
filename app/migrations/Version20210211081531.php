<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20210211081531 extends AbstractMauticMigration
{
    private string $uuidColumn = 'uuid';

    /**
     * @var string[]
     */
    private array $tableList = [
        'assets',
        'campaign_events',
        'campaigns',
        'categories',
        'dynamic_content',
        'emails',
        'focus',
        'form_actions',
        'form_fields',
        'forms',
        'lead_fields',
        'lead_lists',
        'lead_tags',
        'message_channels',
        'messages',
        'pages',
        'point_trigger_events',
        'point_triggers',
        'points',
        'push_notifications',
        'reports',
        'stages',
        'sms_messages',
        'monitoring',
    ];

    public function preUp(Schema $schema): void
    {
        foreach ($this->tableList as $key => $table) {
            if ($schema->getTable($this->prefix.$table)->hasColumn($this->uuidColumn)) {
                unset($this->tableList[$key]);
            }
        }
    }

    public function up(Schema $schema): void
    {
        if (0 === count($this->tableList)) {
            return;
        }

        $statements = [];
        foreach ($this->tableList as $table) {
            // When we migrate to MySql 8.0.13+,
            // the below commented statement would suffice for settings default values using expression
            // ALTER TABLE `{$this->prefix}{$table}` ALTER `{$this->uuidColumn}` SET DEFAULT bin_to_uuid(UUID());

            $statements[] = "ALTER TABLE `{$this->prefix}{$table}` ADD COLUMN `{$this->uuidColumn}` char(36) default NULL;";
            $statements[] = "UPDATE `{$this->prefix}{$table}` SET `{$this->uuidColumn}` = UUID() WHERE `{$this->uuidColumn}` IS NULL;";
        }
        $batchSql = implode(' ', $statements);
        $this->addSql($batchSql);
    }
}
