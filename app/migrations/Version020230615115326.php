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
            $prefixedTable = $this->prefix.$table;

            foreach ($columns as $column) {
                if (DatabasePlatform::isPostgreSQL($platform)) {
                    // PostgreSQL needs explicit USING for safe cast (TEXT -> jsonb)
                    // Assumes existing data is valid JSON or NULL – if not, add custom validation/cleanup first
                    $this->addSql(
                        "ALTER TABLE {$prefixedTable} ".
                        "ALTER COLUMN {$column} TYPE jsonb ".
                        "USING {$column}::jsonb"
                    );
                } else {
                    // MySQL/MariaDB – keep original or similar
                    $this->addSql(
                        "ALTER TABLE `{$prefixedTable}` ".
                        "CHANGE `{$column}` `{$column}` JSON ".
                        ('properties' === $column || 'headers' === $column || 'details' === $column || 'internal_storage' === $column ? 'NOT NULL' : 'DEFAULT NULL').
                        " COMMENT '(DC2Type:json)'"
                    );
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
