<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Entity\Submission;
use Mautic\FormBundle\Entity\SubmissionTotal;

/**
 * Form submission totals per hour, kept apart from the submission rows so the stats stay when results are deleted.
 */
final class Version20261113100000 extends PreUpAssertionMigration
{
    protected const TABLE_NAME = SubmissionTotal::TABLE_NAME;

    protected function preUpAssertions(): void
    {
        $this->skipAssertion(
            fn (Schema $schema) => $schema->hasTable($this->getPrefixedTableName()),
            "Table {$this->getPrefixedTableName()} already exists"
        );
    }

    public function up(Schema $schema): void
    {
        $totals      = $this->getPrefixedTableName();
        $forms       = $this->getPrefixedTableName(Form::TABLE_NAME);
        $submissions = $this->getPrefixedTableName(Submission::TABLE_NAME);

        $this->addSql("CREATE TABLE `{$totals}` (
            `id`      INT UNSIGNED AUTO_INCREMENT NOT NULL,
            `form_id` INT UNSIGNED NOT NULL,
            `hour`    DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
            `total`   INT NOT NULL,
            INDEX IDX_EA3A22DE5FF69B7D (`form_id`),
            UNIQUE INDEX form_submission_totals_form_hour (`form_id`, `hour`),
            PRIMARY KEY(`id`)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC");

        $this->addSql("ALTER TABLE `{$totals}` ADD CONSTRAINT FK_EA3A22DE5FF69B7D FOREIGN KEY (`form_id`) REFERENCES `{$forms}` (`id`) ON DELETE CASCADE");

        // Start the totals from the submissions already made, so the stats look the same as before the upgrade
        $this->addSql("INSERT INTO `{$totals}` (`form_id`, `hour`, `total`)
            SELECT `form_id`, DATE_FORMAT(`date_submitted`, '%Y-%m-%d %H:00:00'), COUNT(*)
            FROM `{$submissions}`
            GROUP BY `form_id`, DATE_FORMAT(`date_submitted`, '%Y-%m-%d %H:00:00')");
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable($this->getPrefixedTableName())) {
            $this->addSql("DROP TABLE `{$this->getPrefixedTableName()}`");
        }
    }
}
