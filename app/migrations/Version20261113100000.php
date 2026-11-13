<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Entity\Submission;
use Mautic\FormBundle\Entity\SubmissionTotal;
use Symfony\Component\Console\Input\ArgvInput;

/**
 * Form submission totals per hour, kept apart from the submission rows so the stats stay when results are deleted.
 * The table is created in up() and filled from the submissions made so far in postUp(), one day at a time.
 */
final class Version20261113100000 extends PreUpAssertionMigration
{
    protected const TABLE_NAME = SubmissionTotal::TABLE_NAME;

    private const FOREIGN_KEY = 'FK_EA3A22DE5FF69B7D';

    private const HOUR_FORMAT = '%Y-%m-%d %H:00:00';

    public function isTransactional(): bool
    {
        return false;
    }

    protected function preUpAssertions(): void
    {
        $this->skipAssertion(
            fn (Schema $schema) => $schema->hasTable($this->getPrefixedTableName()),
            "Table {$this->getPrefixedTableName()} already exists"
        );
        $this->skipAssertion(
            fn (Schema $schema): bool => $schema->hasTable($this->getPrefixedTableName()) && $schema->getTable($this->getPrefixedTableName())->hasForeignKey(self::FOREIGN_KEY),
            "Table {$this->getPrefixedTableName()} already has its foreign key"
        );
        $this->skipAssertion(
            fn (Schema $schema): bool => $schema->hasTable($this->getPrefixedTableName()) && $this->isBackfillDone(),
            "Table {$this->getPrefixedTableName()} already holds the totals of the submissions made so far"
        );
    }

    public function up(Schema $schema): void
    {
        $totals = $this->getPrefixedTableName();
        $forms  = $this->getPrefixedTableName(Form::TABLE_NAME);

        if (!$schema->hasTable($totals)) {
            $this->addSql("CREATE TABLE `{$totals}` (
                `id`      INT UNSIGNED AUTO_INCREMENT NOT NULL,
                `form_id` INT UNSIGNED NOT NULL,
                `hour`    DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                `total`   INT NOT NULL,
                INDEX IDX_EA3A22DE5FF69B7D (`form_id`),
                UNIQUE INDEX form_submission_totals_form_hour (`form_id`, `hour`),
                PRIMARY KEY(`id`)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC");
        }

        if (!$schema->hasTable($totals) || !$schema->getTable($totals)->hasForeignKey(self::FOREIGN_KEY)) {
            $this->addSql("ALTER TABLE `{$totals}` ADD CONSTRAINT ".self::FOREIGN_KEY." FOREIGN KEY (`form_id`) REFERENCES `{$forms}` (`id`) ON DELETE CASCADE");
        }
    }

    /**
     * Starts the totals from the submissions already made, so the stats look the same as before the upgrade. Each day
     * is one statement that sets the hour totals of that day, so a run that stopped halfway can simply be run again.
     */
    public function postUp(Schema $schema): void
    {
        // The backfill writes directly, so Doctrine's dry-run protection does not apply.
        if ((new ArgvInput())->hasParameterOption('--dry-run')) {
            $this->write('<comment>Dry run: skipping the form submission totals backfill</comment>');

            return;
        }

        if ($this->isBackfillDone()) {
            return;
        }

        $submissions = $this->getPrefixedTableName(Submission::TABLE_NAME);
        $range       = $this->connection->fetchAssociative("SELECT MIN(date_submitted) AS first, MAX(date_submitted) AS last FROM `{$submissions}`");

        if (!is_array($range) || null === $range['first']) {
            return;
        }

        $day     = (new \DateTimeImmutable((string) $range['first']))->setTime(0, 0);
        $lastDay = (new \DateTimeImmutable((string) $range['last']))->setTime(0, 0);

        while ($day <= $lastDay) {
            $nextDay = $day->modify('+1 day');

            $this->connection->executeStatement(
                "INSERT INTO `{$this->getPrefixedTableName()}` (`form_id`, `hour`, `total`)
                SELECT `form_id`, DATE_FORMAT(`date_submitted`, '".self::HOUR_FORMAT."'), COUNT(*)
                FROM `{$submissions}`
                WHERE `date_submitted` >= ? AND `date_submitted` < ?
                GROUP BY `form_id`, DATE_FORMAT(`date_submitted`, '".self::HOUR_FORMAT."')
                ON DUPLICATE KEY UPDATE `total` = VALUES(`total`)",
                [$day->format('Y-m-d H:i:s'), $nextDay->format('Y-m-d H:i:s')]
            );

            $day = $nextDay;
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable($this->getPrefixedTableName())) {
            $this->addSql("DROP TABLE `{$this->getPrefixedTableName()}`");
        }
    }

    /**
     * The days are filled in order, so the totals are complete once the hour of the newest submission has its row.
     */
    private function isBackfillDone(): bool
    {
        $submissions = $this->getPrefixedTableName(Submission::TABLE_NAME);

        return (bool) $this->connection->fetchOne(
            "SELECT NOT EXISTS (SELECT 1 FROM `{$submissions}`)
                OR EXISTS (
                    SELECT 1 FROM `{$this->getPrefixedTableName()}` t
                    WHERE t.`hour` = (SELECT DATE_FORMAT(MAX(`date_submitted`), '".self::HOUR_FORMAT."') FROM `{$submissions}`)
                )"
        );
    }
}
