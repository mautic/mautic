<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20250127092202 extends PreUpAssertionMigration
{
    protected const TABLE_NAME  = 'dynamic_content';
    private const COLUMN_NAME   = 'type';

    protected function preUpAssertions(): void
    {
        $this->skipAssertion(fn (Schema $schema) => $schema->getTable($this->getPrefixedTableName(self::TABLE_NAME))->hasColumn(self::COLUMN_NAME),
            sprintf('Column %s already exists in table %s',
                self::COLUMN_NAME, $this->getPrefixedTableName(self::TABLE_NAME))
        );
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable($this->getPrefixedTableName(self::TABLE_NAME));
        $table->addColumn(self::COLUMN_NAME, Types::STRING, ['default' => 'html', 'length' => 10]);
    }

    public function down(Schema $schema): void
    {
        $schema->getTable($this->getPrefixedTableName(self::TABLE_NAME))->dropColumn(self::COLUMN_NAME);
    }
}
