<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Mautic\AssetBundle\Entity\Asset;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;
use Mautic\CoreBundle\Loader\ParameterLoader;

final class Version20261028145757 extends AbstractMauticMigration
{
    protected const TABLE_NAME = Asset::TABLE_NAME;
    private const COLUMN_NAME  = 'track_downloads';

    public function getDescription(): string
    {
        return 'Add the per-asset download tracking toggle';
    }

    public function up(Schema $schema): void
    {
        // Not skipped when the column exists, so a rerun after a failed backfill still runs the backfill
        $table = $schema->getTable($this->getPrefixedTableName());
        if (!$table->hasColumn(self::COLUMN_NAME)) {
            $table->addColumn(self::COLUMN_NAME, Types::BOOLEAN, ['default' => true]);
        }
    }

    public function postUp(Schema $schema): void
    {
        // A dry run doesn't add the column, so there's nothing to backfill yet
        $columns = $this->connection->createSchemaManager()->listTableColumns($this->getPrefixedTableName());
        if (!array_key_exists(self::COLUMN_NAME, $columns)) {
            return;
        }

        // Existing assets start from the global setting, so turning it off before the upgrade still holds
        if ($this->isDownloadTrackingOffGlobally()) {
            $this->connection->executeStatement(
                sprintf('UPDATE %s SET %s = 0', $this->getPrefixedTableName(), self::COLUMN_NAME)
            );
        }
    }

    public function down(Schema $schema): void
    {
        $schema->getTable($this->getPrefixedTableName())
            ->dropColumn(self::COLUMN_NAME);
    }

    private function isDownloadTrackingOffGlobally(): bool
    {
        $localParameters = (new ParameterLoader())->getLocalParameterBag();

        return $localParameters->has('track_asset_downloads') && !$localParameters->get('track_asset_downloads');
    }
}
