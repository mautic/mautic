<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;
use Mautic\FormBundle\Entity\Submission;

final class Version20261119000000 extends PreUpAssertionMigration
{
    protected const TABLE_NAME = Submission::TABLE_NAME;

    private const COLUMN_NAME = 'deleted_lead_id';

    private const INDEX_NAME = 'form_submission_deleted_lead';

    protected function preUpAssertions(): void
    {
        $this->skipAssertion(
            fn (Schema $schema) => $schema->getTable($this->getPrefixedTableName())->hasColumn(self::COLUMN_NAME),
            "Table {$this->getPrefixedTableName()} already has the '".self::COLUMN_NAME."' column"
        );

        $this->skipAssertion(
            fn (Schema $schema) => $schema->getTable($this->getPrefixedTableName())->hasIndex($this->getIndexName()),
            "Table {$this->getPrefixedTableName()} already has the '{$this->getIndexName()}' index"
        );
    }

    public function up(Schema $schema): void
    {
        $tableName = $this->getPrefixedTableName();
        $table     = $schema->getTable($tableName);

        if (!$table->hasColumn(self::COLUMN_NAME)) {
            $this->addSql("ALTER TABLE {$tableName} ADD ".self::COLUMN_NAME.' BIGINT UNSIGNED DEFAULT NULL');
        }

        if (!$table->hasIndex($this->getIndexName())) {
            $this->addSql("CREATE INDEX {$this->getIndexName()} ON {$tableName} (".self::COLUMN_NAME.')');
        }
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable($this->getPrefixedTableName());

        if ($table->hasIndex($this->getIndexName())) {
            $table->dropIndex($this->getIndexName());
        }

        if ($table->hasColumn(self::COLUMN_NAME)) {
            $table->dropColumn(self::COLUMN_NAME);
        }
    }

    private function getIndexName(): string
    {
        return $this->prefix.self::INDEX_NAME;
    }
}
