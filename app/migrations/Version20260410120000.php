<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20260410120000 extends PreUpAssertionMigration
{
    private string $tableName;

    private string $categoryFk;

    private function initTableNames(): void
    {
        $this->tableName  = $this->prefix.'company_segments';
        $this->categoryFk = $this->generatePropertyName('company_segments', 'fk', ['category_id']);
    }

    protected function preUpAssertions(): void
    {
        $this->initTableNames();

        $this->skipAssertion(
            fn (Schema $schema) => $schema->hasTable($this->tableName),
            "Table {$this->tableName} already exists"
        );

        $this->skipAssertion(
            fn (Schema $schema) => $schema->getTable($this->tableName)->hasForeignKey($this->categoryFk),
            "Foreign key {$this->categoryFk} already exists"
        );
    }

    public function up(Schema $schema): void
    {
        $this->initTableNames();

        $this->addSql("CREATE TABLE `{$this->tableName}`
(
    `id`                  INT UNSIGNED AUTO_INCREMENT NOT NULL,
    `category_id`         INT UNSIGNED DEFAULT NULL,
    `is_published`        TINYINT(1)                  NOT NULL,
    `date_added`          DATETIME     DEFAULT NULL,
    `created_by`          INT          DEFAULT NULL,
    `created_by_user`     VARCHAR(191) DEFAULT NULL,
    `date_modified`       DATETIME     DEFAULT NULL,
    `modified_by`         INT          DEFAULT NULL,
    `modified_by_user`    VARCHAR(191) DEFAULT NULL,
    `checked_out`         DATETIME     DEFAULT NULL,
    `checked_out_by`      INT          DEFAULT NULL,
    `checked_out_by_user` VARCHAR(191) DEFAULT NULL,
    `name`                VARCHAR(191)                NOT NULL,
    `description`         LONGTEXT     DEFAULT NULL,
    `alias`               VARCHAR(191)                NOT NULL,
    `public_name`         VARCHAR(191)                NOT NULL,
    `filters`             LONGTEXT                    NOT NULL COMMENT '(DC2Type:json)',
    `last_built_date`     DATETIME     DEFAULT NULL,
    `last_built_time`     DOUBLE       DEFAULT NULL,
    PRIMARY KEY (`id`),
    INDEX `company_segment_alias` (`alias`)
) DEFAULT CHARACTER SET utf8mb4
  COLLATE `utf8mb4_unicode_ci`
  ENGINE = InnoDB
  ROW_FORMAT = DYNAMIC;");

        $this->addSql("ALTER TABLE `{$this->tableName}` ADD CONSTRAINT `{$this->categoryFk}` FOREIGN KEY (`category_id`) REFERENCES `{$this->prefix}categories` (`id`) ON DELETE SET NULL");
    }

    public function down(Schema $schema): void
    {
        $this->initTableNames();

        if ($schema->hasTable($this->tableName)) {
            $this->addSql("DROP TABLE {$this->tableName}");
        }
    }
}
