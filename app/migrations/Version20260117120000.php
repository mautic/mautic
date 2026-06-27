<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20260117120000 extends PreUpAssertionMigration
{
    private const COLUMN_NAME  = 'send_to_dnc';
    protected const TABLE_NAME = 'emails';

    protected function preUpAssertions(): void
    {
        $this->skipAssertion(
            fn (Schema $schema) => $schema->getTable($this->getPrefixedTableName())->hasColumn(self::COLUMN_NAME),
            sprintf('Column %s.%s already exists', self::TABLE_NAME, self::COLUMN_NAME)
        );
    }

    public function up(Schema $schema): void
    {
        $schema->getTable($this->getPrefixedTableName())
            ->addColumn(self::COLUMN_NAME, Types::BOOLEAN)
            ->setNotnull(true)
            ->setDefault(false);
    }

    public function down(Schema $schema): void
    {
        $schema->getTable($this->getPrefixedTableName())
            ->dropColumn(self::COLUMN_NAME);
    }
}
