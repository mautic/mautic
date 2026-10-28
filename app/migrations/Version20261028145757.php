<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Mautic\AssetBundle\Entity\Asset;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;
use Mautic\CoreBundle\Loader\ParameterLoader;

final class Version20261028145757 extends PreUpAssertionMigration
{
    protected const TABLE_NAME = Asset::TABLE_NAME;
    private const COLUMN_NAME  = 'track_downloads';

    public function getDescription(): string
    {
        return 'Add the per-asset download tracking toggle';
    }

    protected function preUpAssertions(): void
    {
        $this->skipAssertion(
            fn (Schema $schema) => $schema->getTable($this->getPrefixedTableName())->hasColumn(self::COLUMN_NAME),
            sprintf('Column %s already exists', self::COLUMN_NAME)
        );
    }

    public function up(Schema $schema): void
    {
        $schema->getTable($this->getPrefixedTableName())
            ->addColumn(self::COLUMN_NAME, Types::BOOLEAN, ['default' => true]);
    }

    public function postUp(Schema $schema): void
    {
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
        $configFile = ParameterLoader::getLocalConfigFile(__DIR__.'/../');
        if (!file_exists($configFile)) {
            return false;
        }

        require $configFile;

        return isset($parameters) && is_array($parameters)
            && array_key_exists('track_asset_downloads', $parameters) && !$parameters['track_asset_downloads'];
    }
}
