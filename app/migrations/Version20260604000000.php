<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Entity\Submission;

final class Version20260604000000 extends PreUpAssertionMigration
{
    protected const TABLE_NAME = Form::TABLE_NAME;

    protected function preUpAssertions(): void
    {
        foreach (['submission_limit', 'submission_limit_message', 'submission_count'] as $column) {
            $this->skipAssertion(
                fn (Schema $schema) => $schema->getTable($this->getPrefixedTableName())->hasColumn($column),
                "Table {$this->getPrefixedTableName()} already has '{$column}' column"
            );
        }
    }

    public function up(Schema $schema): void
    {
        $forms = $this->getPrefixedTableName();
        $table = $schema->getTable($forms);

        if (!$table->hasColumn('submission_limit')) {
            $this->addSql("ALTER TABLE {$forms} ADD submission_limit INT DEFAULT NULL");
        }

        if (!$table->hasColumn('submission_limit_message')) {
            $column = $this->platform->getColumnDeclarationSQL('submission_limit_message', ['type' => Type::getType(Types::TEXT), 'notnull' => false]);
            $this->addSql("ALTER TABLE {$forms} ADD {$column}");
        }

        if (!$table->hasColumn('submission_count')) {
            $this->addSql("ALTER TABLE {$forms} ADD submission_count INT DEFAULT 0 NOT NULL");
        }

        // Recount partial upgrades too: the counter column may exist before its backfill succeeds.
        $submissions = $this->getPrefixedTableName(Submission::TABLE_NAME);
        $this->addSql("UPDATE {$forms} f SET submission_count = (SELECT COUNT(*) FROM {$submissions} s WHERE s.form_id = f.id)");
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable($this->getPrefixedTableName());

        if ($table->hasColumn('submission_limit')) {
            $table->dropColumn('submission_limit');
        }
        if ($table->hasColumn('submission_limit_message')) {
            $table->dropColumn('submission_limit_message');
        }
        if ($table->hasColumn('submission_count')) {
            $table->dropColumn('submission_count');
        }
    }
}
