<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;
use Mautic\CoreBundle\Doctrine\DatabasePlatform;

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
        $column = $this->platform->quoteIdentifier($this->uuidColumn);
        $uuid   = DatabasePlatform::isPostgreSQL($this->platform) ? 'gen_random_uuid()::text' : 'UUID()';

        foreach ($this->tableList as $table) {
            $tableName = $this->platform->quoteIdentifier($this->getPrefixedTableName($table));
            $this->addSql("ALTER TABLE {$tableName} ADD COLUMN {$column} CHAR(36) DEFAULT NULL");
            $this->addSql("UPDATE {$tableName} SET {$column} = {$uuid} WHERE {$column} IS NULL");
        }
    }
}
