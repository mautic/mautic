<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Entity\Submission;

final class Version20260604000000 extends PreUpAssertionMigration
{
    protected const TABLE_NAME = Form::TABLE_NAME;

    protected function preUpAssertions(): void
    {
        $this->skipAssertion(
            fn (Schema $schema) => $schema->getTable($this->getPrefixedTableName())->hasColumn('submission_limit'),
            "Table {$this->getPrefixedTableName()} already has 'submission_limit' column"
        );
    }

    public function up(Schema $schema): void
    {
        $forms = $this->getPrefixedTableName();
        $table = $schema->getTable($forms);

        if (!$table->hasColumn('submission_limit')) {
            $this->addSql("ALTER TABLE {$forms} ADD submission_limit INT DEFAULT NULL");
        }

        if (!$table->hasColumn('submission_limit_message')) {
            $this->addSql("ALTER TABLE {$forms} ADD submission_limit_message LONGTEXT DEFAULT NULL");
        }

        if (!$table->hasColumn('submission_count')) {
            $submissions = $this->getPrefixedTableName(Submission::TABLE_NAME);

            $this->addSql("ALTER TABLE {$forms} ADD submission_count INT DEFAULT 0 NOT NULL");
            // Backfill the counter from existing submissions so the limit accounts for historical data.
            $this->addSql("UPDATE {$forms} f SET f.submission_count = (SELECT COUNT(*) FROM {$submissions} s WHERE s.form_id = f.id)");
        }
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
