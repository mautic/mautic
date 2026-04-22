<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20260410120001 extends PreUpAssertionMigration
{
    private string $oldTableName;

    private string $newTableName;

    private string $segmentFk;

    private string $companyFk;

    private function initTableNames(): void
    {
        $this->oldTableName = $this->prefix.'companies_segments';
        $this->newTableName = $this->prefix.'company_segments_companies';
        $this->segmentFk    = $this->generatePropertyName('company_segments_companies', 'fk', ['segment_id']);
        $this->companyFk    = $this->generatePropertyName('company_segments_companies', 'fk', ['company_id']);
    }

    protected function preUpAssertions(): void
    {
        $this->initTableNames();

        $this->skipAssertion(
            fn (Schema $schema) => $schema->hasTable($this->newTableName),
            "Table {$this->newTableName} already exists"
        );

        $this->skipAssertion(
            fn (Schema $schema) => $schema->hasTable($this->newTableName) && $schema->getTable($this->newTableName)->hasForeignKey($this->segmentFk),
            "Foreign key {$this->segmentFk} already exists"
        );

        $this->skipAssertion(
            fn (Schema $schema) => $schema->hasTable($this->newTableName) && $schema->getTable($this->newTableName)->hasForeignKey($this->companyFk),
            "Foreign key {$this->companyFk} already exists"
        );
    }

    public function up(Schema $schema): void
    {
        $this->initTableNames();

        if ($schema->hasTable($this->oldTableName)) {
            $this->addSql("RENAME TABLE `{$this->oldTableName}` TO `{$this->newTableName}`");
        } else {
            $this->addSql("CREATE TABLE `{$this->newTableName}`
(
    `segment_id`        INT UNSIGNED NOT NULL,
    `company_id`        INT          NOT NULL,
    `date_added`        DATETIME     NOT NULL,
    `manually_removed`  TINYINT(1)   NOT NULL,
    `manually_added`    TINYINT(1)   NOT NULL,
    PRIMARY KEY (`segment_id`, `company_id`),
    INDEX `companies_segment_manually_removed` (`manually_removed`)
) DEFAULT CHARACTER SET utf8mb4
  COLLATE `utf8mb4_unicode_ci`
  ENGINE = InnoDB
  ROW_FORMAT = DYNAMIC;");

            $this->addSql("ALTER TABLE `{$this->newTableName}` ADD CONSTRAINT `{$this->segmentFk}` FOREIGN KEY (`segment_id`) REFERENCES `{$this->prefix}company_segments` (`id`) ON DELETE CASCADE");
            $this->addSql("ALTER TABLE `{$this->newTableName}` ADD CONSTRAINT `{$this->companyFk}` FOREIGN KEY (`company_id`) REFERENCES `{$this->prefix}companies` (`id`) ON DELETE CASCADE");
        }
    }

    public function down(Schema $schema): void
    {
        $this->initTableNames();

        if ($schema->hasTable($this->newTableName)) {
            $this->addSql("DROP TABLE {$this->newTableName}");
        }
    }
}
