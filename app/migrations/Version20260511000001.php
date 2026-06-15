<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;
use Mautic\LeadBundle\Entity\FieldGroup;

final class Version20260511000001 extends PreUpAssertionMigration
{
    private string $tableName;

    private function initTableNames(): void
    {
        $this->tableName = $this->prefix.FieldGroup::TABLE_NAME;
    }

    protected function preUpAssertions(): void
    {
        $this->initTableNames();

        $this->skipAssertion(
            fn (Schema $schema) => $schema->hasTable($this->tableName),
            "Table {$this->tableName} already exists"
        );
    }

    public function up(Schema $schema): void
    {
        $this->initTableNames();

        $this->addSql("CREATE TABLE `{$this->tableName}`
(
    `id`                  INT UNSIGNED AUTO_INCREMENT NOT NULL,
    `is_published`        TINYINT(1)                  NOT NULL DEFAULT 1,
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
    `alias`               VARCHAR(191)                NOT NULL,
    `description`         LONGTEXT     DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `UNIQ_lead_field_group_alias` (`alias`)
) DEFAULT CHARACTER SET utf8mb4
  COLLATE `utf8mb4_unicode_ci`
  ENGINE = InnoDB
  ROW_FORMAT = DYNAMIC;");
    }

    public function down(Schema $schema): void
    {
        $this->initTableNames();

        if ($schema->hasTable($this->tableName)) {
            $this->addSql("DROP TABLE `{$this->tableName}`");
        }
    }
}
