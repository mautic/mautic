<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20241216052760 extends PreUpAssertionMigration
{
    protected const TABLE_NAME = 'lead_fields';

    protected function preUpAssertions(): void
    {
        $this->skipAssertion(
            fn (Schema $schema) => $schema->getTable($this->getPrefixedTableName())->hasColumn('is_searchable'),
            sprintf('Column %s already exists', 'is_searchable')
        );
    }

    public function up(Schema $schema): void
    {
        // Add queries to modify the database schema. Examples:
        $table = $schema->getTable($this->getPrefixedTableName());
        $table->addColumn('is_searchable', Types::BOOLEAN)->setDefault(false);
    }
}
