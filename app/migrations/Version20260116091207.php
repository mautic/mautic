<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20260116091207 extends PreUpAssertionMigration
{
    protected const TABLE_NAME = 'assets';

    protected function preUpAssertions(): void
    {
        // Skip if alias column is already nullable
        $this->skipAssertion(
            fn (Schema $schema) => !$schema
                ->getTable($this->getPrefixedTableName())
                ->getColumn('alias')
                ->getNotnull(),
            'Column assets.alias is already nullable'
        );
    }

    public function up(Schema $schema): void
    {
        $table  = $schema->getTable($this->getPrefixedTableName());
        $column = $table->getColumn('alias');

        $column->setNotnull(false);
    }

    public function down(Schema $schema): void
    {
        $table  = $schema->getTable($this->getPrefixedTableName());
        $column = $table->getColumn('alias');

        $column->setNotnull(true);
    }
}
