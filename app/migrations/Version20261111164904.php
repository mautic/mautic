<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;
use Mautic\FormBundle\Entity\Field;

final class Version20261111164904 extends PreUpAssertionMigration
{
    private const string COLUMN_NAME  = 'lead_field';

    protected const string TABLE_NAME = Field::TABLE_NAME;

    protected function preUpAssertions(): void
    {
        $this->skipAssertion(
            fn (Schema $schema) => !$schema->getTable($this->getPrefixedTableName())->hasColumn(self::COLUMN_NAME),
            sprintf('Column %s.%s does not exist', self::TABLE_NAME, self::COLUMN_NAME)
        );
    }

    public function up(Schema $schema): void
    {
        $tableName = $this->getPrefixedTableName();

        // Copy values of fields that were never mapped to mapped_object and mapped_field
        $this->addSql("UPDATE {$tableName}
            SET mapped_object = CASE
                WHEN lead_field LIKE 'company%' AND lead_field != 'company' THEN 'company'
                ELSE 'contact'
            END, mapped_field = lead_field
            WHERE lead_field IS NOT NULL AND lead_field != '' AND (mapped_field IS NULL OR mapped_field = '')");

        $this->addSql(sprintf('ALTER TABLE %s DROP %s', $tableName, self::COLUMN_NAME));
    }

    public function down(Schema $schema): void
    {
        $tableName = $this->getPrefixedTableName();

        $this->addSql(sprintf('ALTER TABLE %s ADD %s VARCHAR(191) DEFAULT NULL', $tableName, self::COLUMN_NAME));
        $this->addSql(sprintf('UPDATE %s SET %s = mapped_field WHERE mapped_field IS NOT NULL', $tableName, self::COLUMN_NAME));
    }
}
