<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20250128065104 extends PreUpAssertionMigration
{
    protected const TABLE_NAME = 'dynamic_content';

    protected function preUpAssertions(): void
    {
        $this->skipAssertion(
            fn (Schema $schema) => $schema->getTable($this->getPrefixedTableName(self::TABLE_NAME))->hasColumn('display_order'),
            'Column order already exists'
        );
    }

    public function up(Schema $schema): void
    {
        $schema
            ->getTable($this->getPrefixedTableName(self::TABLE_NAME))
            ->addColumn('display_order', Types::INTEGER, ['notnull' => false]);
    }

    public function down(Schema $schema): void
    {
        $schema->getTable($this->getPrefixedTableName(self::TABLE_NAME))->dropColumn('display_order');
    }
}
