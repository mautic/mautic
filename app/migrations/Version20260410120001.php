<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20260410120001 extends PreUpAssertionMigration
{
    private string $tableName;

    private string $segmentFk;

    private string $companyFk;

    private function initTableNames(): void
    {
        $this->tableName = $this->prefix.'companies_segments';
        $this->segmentFk = $this->generatePropertyName('companies_segments', 'fk', ['segment_id']);
        $this->companyFk = $this->generatePropertyName('companies_segments', 'fk', ['company_id']);
    }

    protected function preUpAssertions(): void
    {
        $this->initTableNames();

        $this->skipAssertion(
            fn (Schema $schema) => $schema->hasTable($this->tableName),
            "Table {$this->tableName} already exists"
        );

        $this->skipAssertion(
            fn (Schema $schema) => $schema->getTable($this->tableName)->hasForeignKey($this->segmentFk),
            "Foreign key {$this->segmentFk} already exists"
        );

        $this->skipAssertion(
            fn (Schema $schema) => $schema->getTable($this->tableName)->hasForeignKey($this->companyFk),
            "Foreign key {$this->companyFk} already exists"
        );
    }

    public function up(Schema $schema): void
    {
        $this->initTableNames();

        $this->addSql("CREATE TABLE `{$this->tableName}`
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

        $this->addSql("ALTER TABLE `{$this->tableName}` ADD CONSTRAINT `{$this->segmentFk}` FOREIGN KEY (`segment_id`) REFERENCES `{$this->prefix}company_segments` (`id`) ON DELETE CASCADE");
        $this->addSql("ALTER TABLE `{$this->tableName}` ADD CONSTRAINT `{$this->companyFk}` FOREIGN KEY (`company_id`) REFERENCES `{$this->prefix}companies` (`id`) ON DELETE CASCADE");
    }

    public function down(Schema $schema): void
    {
        $this->initTableNames();

        if ($schema->hasTable($this->tableName)) {
            $this->addSql("DROP TABLE {$this->tableName}");
        }
    }
}
