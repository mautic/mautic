<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20260629095751 extends PreUpAssertionMigration
{
    private const REMOVED_FIELDS = ['googleplus', 'foursquare', 'skype'];

    public function getDescription(): string
    {
        return 'Update the default contact social media fields.';
    }

    protected function preUpAssertions(): void
    {
        $leadFieldsTableName = "{$this->prefix}lead_fields";
        $leadsTableName      = "{$this->prefix}leads";

        $this->skipAssertion(
            function (Schema $schema) use ($leadsTableName): bool {
                if (!$schema->hasTable($leadsTableName)) {
                    return false;
                }

                $leadsTable = $schema->getTable($leadsTableName);

                return $leadsTable->hasColumn('tiktok')
                    && $this->hasIndexForColumn($leadsTable, 'tiktok')
                    && $leadsTable->hasColumn('youtube')
                    && $this->hasIndexForColumn($leadsTable, 'youtube');
            },
            'The TikTok and YouTube contact fields and indexes already exist.'
        );

        $this->skipAssertion(
            fn (Schema $schema): bool => $schema->hasTable($leadFieldsTableName)
                && 0 === (int) $this->connection->fetchOne(
                    "SELECT COUNT(*) FROM `{$leadFieldsTableName}` WHERE `object` = 'lead' AND `alias` = 'twitter' AND `label` IN ('Twitter', 'mautic.lead.field.twitter')"
                ),
            'The Twitter contact field label is already updated.'
        );
    }

    public function up(Schema $schema): void
    {
        $leadFieldsTableName = "{$this->prefix}lead_fields";
        $leadsTableName      = "{$this->prefix}leads";
        $leadsTable          = $schema->getTable($leadsTableName);

        $this->addSql(
            "DELETE FROM `{$leadFieldsTableName}` WHERE `object` = 'lead' AND `alias` IN ('googleplus', 'foursquare', 'skype')"
        );
        $this->addSql(
            "UPDATE `{$leadFieldsTableName}` SET `label` = 'X' WHERE `object` = 'lead' AND `alias` = 'twitter' AND `label` IN ('Twitter', 'mautic.lead.field.twitter')"
        );

        foreach (self::REMOVED_FIELDS as $alias) {
            $indexName = "{$alias}_search";
            if ($leadsTable->hasIndex($indexName)) {
                $this->addSql("ALTER TABLE `{$leadsTableName}` DROP INDEX `{$indexName}`");
            }

            if ($leadsTable->hasColumn($alias)) {
                $this->addSql("ALTER TABLE `{$leadsTableName}` DROP COLUMN `{$alias}`");
            }
        }

        $this->addSocialField($leadsTable, $leadFieldsTableName, $leadsTableName, 'tiktok', 'TikTok');
        $this->addSocialField($leadsTable, $leadFieldsTableName, $leadsTableName, 'youtube', 'YouTube');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('The removed social field data cannot be restored.');
    }

    private function addSocialField(
        Table $leadsTable,
        string $leadFieldsTableName,
        string $leadsTableName,
        string $alias,
        string $label,
    ): void {
        $this->addSql(
            "INSERT INTO `{$leadFieldsTableName}` (
                `is_published`,
                `label`,
                `alias`,
                `type`,
                `field_group`,
                `is_required`,
                `is_fixed`,
                `is_visible`,
                `is_short_visible`,
                `is_listable`,
                `is_publicly_updatable`,
                `is_unique_identifer`,
                `is_index`,
                `field_order`,
                `object`,
                `properties`,
                `column_is_not_created`,
                `column_is_not_removed`,
                `original_is_published_value`,
                `uuid`
            )
            SELECT
                1,
                '{$label}',
                '{$alias}',
                'text',
                'social',
                0,
                0,
                1,
                0,
                1,
                0,
                0,
                0,
                (SELECT COALESCE(MAX(`field_order`), 0) + 1 FROM `{$leadFieldsTableName}` AS `field_order_source` WHERE `object` = 'lead'),
                'lead',
                'a:0:{}',
                0,
                0,
                0,
                UUID()
            WHERE NOT EXISTS (SELECT 1 FROM `{$leadFieldsTableName}` AS `existing_field` WHERE `alias` = '{$alias}')"
        );

        if (!$leadsTable->hasColumn($alias)) {
            $this->addSql("ALTER TABLE `{$leadsTableName}` ADD `{$alias}` VARCHAR(191) DEFAULT NULL");
        }

        if (!$this->hasIndexForColumn($leadsTable, $alias)) {
            $this->addSql("CREATE INDEX `{$alias}_search` ON `{$leadsTableName}` (`{$alias}`)");
        }
    }

    private function hasIndexForColumn(Table $table, string $column): bool
    {
        foreach ($table->getIndexes() as $index) {
            if ([$column] === $index->getColumns()) {
                return true;
            }
        }

        return false;
    }
}
