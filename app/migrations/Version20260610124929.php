<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20260610124929 extends PreUpAssertionMigration
{
    protected const TABLE_NAME = 'focus';
    private const COLUMN_NAME  = 'filters';

    protected function preUpAssertions(): void
    {
        $this->skipAssertion(fn (Schema $schema) => $schema->getTable($this->getPrefixedTableName(self::TABLE_NAME))->hasColumn(self::COLUMN_NAME),
            sprintf('Column %s already exists in table %s',
                self::COLUMN_NAME, $this->getPrefixedTableName(self::TABLE_NAME))
        );
    }

    public function up(Schema $schema): void
    {
        $this->addSql(sprintf(
            "ALTER TABLE `%s` ADD `%s` LONGTEXT DEFAULT NULL COMMENT '(DC2Type:array)'",
            $this->getPrefixedTableName(self::TABLE_NAME),
            self::COLUMN_NAME
        ));
    }

    public function down(Schema $schema): void
    {
        $schema->getTable($this->getPrefixedTableName(self::TABLE_NAME))->dropColumn(self::COLUMN_NAME);
    }
}
