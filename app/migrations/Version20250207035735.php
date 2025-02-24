<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20250207035735 extends PreUpAssertionMigration
{
    protected const TABLE_NAME = 'lead_fields';

    protected function preUpAssertions(): void
    {
        $this->skipAssertion(
            fn (Schema $schema) => ($column = $schema->getTable($this->getPrefixedTableName())->getColumn('is_short_visible'))
                && false === $column->getDefault(),
            sprintf('Column %s already has a default set', 'is_short_visible')
        );
    }

    public function up(Schema $schema): void
    {
        // Update the table schema
        $table = $schema->getTable($this->getPrefixedTableName());
        $table->getColumn('is_short_visible')->setDefault(false)->setNotnull(true);

        // Update the existing records.
        $this->connection->executeStatement(sprintf('UPDATE %s SET is_short_visible = FALSE WHERE is_short_visible IS NULL', $table->getName()));
    }
}
