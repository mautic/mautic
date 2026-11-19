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
    }

    public function up(Schema $schema): void
    {
        $this->addSql(sprintf(
            'ALTER TABLE %s ADD %s BIGINT UNSIGNED DEFAULT NULL, ADD INDEX %s (%s)',
            $this->getPrefixedTableName(),
            self::COLUMN_NAME,
            $this->prefix.self::INDEX_NAME,
            self::COLUMN_NAME
        ));
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable($this->getPrefixedTableName());

        if ($table->hasIndex($this->prefix.self::INDEX_NAME)) {
            $table->dropIndex($this->prefix.self::INDEX_NAME);
        }

        if ($table->hasColumn(self::COLUMN_NAME)) {
            $table->dropColumn(self::COLUMN_NAME);
        }
    }
}
