<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;
use Mautic\CoreBundle\Doctrine\DatabasePlatform;

/**
 * This migration must run first otherwise the pre-up assertions for other migrations will fail on M5.
 */
final class Version020230615115326 extends AbstractMauticMigration
{
    public function getDescription(): string
    {
        return 'Change various properties/JSON fields to JSON type with DC2Type comment';
    }

    public function up(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();

        $tables = [
            'message_channels'    => ['properties'],
            'emails'              => ['headers'],
            'form_fields'         => ['conditions', 'validation'],
            'dynamic_content'     => ['utm_tags'],
            'sms_message_stats'   => ['details'],
            'sync_object_mapping' => ['internal_storage'],
            'imports'             => ['properties'],
            'lead_event_log'      => ['properties'],
            'reports'             => ['settings'],
            'tweet_stats'         => ['response_details'],
        ];

        foreach ($tables as $table => $columns) {
            $prefixedTable = $platform->quoteIdentifier($this->getPrefixedTableName($table));
            $notNull       = in_array($table, ['message_channels', 'emails', 'sms_message_stats', 'sync_object_mapping'], true);

            foreach ($columns as $column) {
                $column = $platform->quoteIdentifier($column);
                if (DatabasePlatform::isPostgreSQL($platform)) {
                    $type = $platform->getJsonTypeDeclarationSQL([]);
                    $this->addSql("ALTER TABLE {$prefixedTable} ALTER COLUMN {$column} TYPE {$type} USING {$column}::{$type}");
                } else {
                    $nullability = $notNull ? 'NOT NULL' : 'DEFAULT NULL';
                    $this->addSql("ALTER TABLE {$prefixedTable} CHANGE {$column} {$column} JSON {$nullability} COMMENT '(DC2Type:json)'");
                }
            }
        }
    }

    public function down(Schema $schema): void
    {
        // Revert to TEXT/LONGTEXT – adjust per original type if known
        // For simplicity, revert to TEXT with same nullability
        $this->throwIrreversibleMigrationException('Downgrade of JSON columns is not implemented.');
    }
}
