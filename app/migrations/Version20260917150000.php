<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

/**
 * Backfill asset_downloads.asset_id for downloads recorded against an emailed asset.
 *
 * AssetModel::trackDownload() only linked the asset outside the system-entry path, so rows
 * written when an asset was delivered as an email attachment were persisted with a null
 * asset_id. Those orphans break the contact timeline once Asset::getSlug() is reached.
 */
final class Version20260917150000 extends PreUpAssertionMigration
{
    protected const TABLE_NAME = 'asset_downloads';

    protected function preUpAssertions(): void
    {
        $this->skipAssertion(
            fn (Schema $schema): bool => 0 === (int) $this->connection->fetchOne(
                sprintf(
                    'SELECT COUNT(*) FROM %s WHERE asset_id IS NULL AND email_id IS NOT NULL',
                    $this->getPrefixedTableName()
                )
            ),
            'No asset download rows need the asset relation backfilled.'
        );
    }

    public function up(Schema $schema): void
    {
        $downloads = $this->getPrefixedTableName();
        $xref      = $this->getPrefixedTableName('email_assets_xref');

        // Restricted to emails carrying exactly one asset. email_assets_xref is many-to-many
        // and nothing else on the row identifies the asset, so a download against a
        // multi-asset email cannot be resolved and is deliberately left alone.
        $this->addSql(<<<SQL
            UPDATE {$downloads} d
            INNER JOIN (
                SELECT email_id, MIN(asset_id) AS asset_id
                  FROM {$xref}
                 GROUP BY email_id
                HAVING COUNT(*) = 1
            ) single_asset ON single_asset.email_id = d.email_id
               SET d.asset_id = single_asset.asset_id
             WHERE d.asset_id IS NULL
               AND d.email_id IS NOT NULL
            SQL);
    }

    public function down(Schema $schema): void
    {
        // Not reversible: once repaired, these rows are indistinguishable from rows that
        // always carried the relation.
    }
}
