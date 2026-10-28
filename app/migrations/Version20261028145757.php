<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Mautic\AssetBundle\Entity\Asset;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20261028145757 extends PreUpAssertionMigration
{
    protected const TABLE_NAME = Asset::TABLE_NAME;
    private const COLUMN_NAME  = 'track_downloads';

    public function getDescription(): string
    {
        return 'Add the per-asset download tracking setting';
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
        // Existing assets keep following the global setting
        $schema->getTable($this->getPrefixedTableName())
            ->addColumn(self::COLUMN_NAME, Types::STRING, ['length' => 10, 'default' => Asset::TRACK_DOWNLOADS_GLOBAL]);
    }

    public function down(Schema $schema): void
    {
        $schema->getTable($this->getPrefixedTableName())
            ->dropColumn(self::COLUMN_NAME);
    }
}
