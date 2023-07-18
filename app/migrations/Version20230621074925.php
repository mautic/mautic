<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;
use Mautic\PointBundle\Entity\Group;
use Mautic\PointBundle\Entity\GroupContactScore;

final class Version20230621074925 extends PreUpAssertionMigration
{
    private string $groupTableName;
    private string $contactScoreTableName;
    private string $contactTableName;
    private string $pointsTableName;
    private string $pointTriggersTableName;
    private string $leadPointsChangeLogTableName;
    private string $contactScoreContactFk;
    private string $contactScoreGroupFk;
    private string $pointsGroupFk;
    private string $pointTriggersGroupFk;
    private string $leadPointsChangeLogGroupFk;

    private function initTableNames(): void
    {
        $this->groupTableName               = $this->generateTableName(Group::TABLE_NAME);
        $this->contactScoreTableName        = $this->generateTableName(GroupContactScore::TABLE_NAME);
        $this->contactTableName             = $this->generateTableName('leads');
        $this->pointsTableName              = $this->generateTableName('points');
        $this->pointTriggersTableName       = $this->generateTableName('point_triggers');
        $this->leadPointsChangeLogTableName = $this->generateTableName('lead_points_change_log');

        $this->contactScoreContactFk      = $this->generatePropertyName($this->contactScoreTableName, 'fk', ['contact_id']);
        $this->contactScoreGroupFk        = $this->generatePropertyName($this->contactScoreTableName, 'fk', ['group_id']);
        $this->pointsGroupFk              = $this->generatePropertyName($this->pointsTableName, 'fk', ['group_id']);
        $this->pointTriggersGroupFk       = $this->generatePropertyName($this->pointTriggersTableName, 'fk', ['group_id']);
        $this->leadPointsChangeLogGroupFk = $this->generatePropertyName($this->leadPointsChangeLogTableName, 'fk', ['group_id']);
    }

    protected function preUpAssertions(): void
    {
        $this->initTableNames();

        $this->assertTableDoesNotExist($this->groupTableName);
        $this->assertTableDoesNotExist($this->contactScoreTableName);

        $this->assertColumnDoesNotExist($this->pointsTableName, 'group_id');
        $this->assertColumnDoesNotExist($this->pointTriggersTableName, 'group_id');
        $this->assertColumnDoesNotExist($this->leadPointsChangeLogTableName, 'group_id');

        $this->assertIndexDoesNotExist($this->contactScoreTableName, $this->contactScoreContactFk);
        $this->assertIndexDoesNotExist($this->contactScoreTableName, $this->contactScoreGroupFk);
        $this->assertIndexDoesNotExist($this->pointsTableName, $this->pointsGroupFk);
        $this->assertIndexDoesNotExist($this->pointTriggersTableName, $this->pointTriggersGroupFk);
        $this->assertIndexDoesNotExist($this->leadPointsChangeLogTableName, $this->leadPointsChangeLogGroupFk);
    }

    public function up(Schema $schema): void
    {
        $this->initTableNames();

        $this->addSql("CREATE TABLE `{$this->groupTableName}`
(
    `id`                  INT UNSIGNED AUTO_INCREMENT NOT NULL,
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
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4
  COLLATE `utf8mb4_unicode_ci`
  ENGINE = InnoDB
  ROW_FORMAT = DYNAMIC;");

        $this->addSql("CREATE TABLE `{$this->contactScoreTableName}`
(
    `contact_id`          BIGINT UNSIGNED NOT NULL,
    `group_id`           INT UNSIGNED    NOT NULL,
    `score`               INT             NOT NULL,
    PRIMARY KEY (`contact_id`, `group_id`)
) DEFAULT CHARACTER SET utf8mb4
  COLLATE `utf8mb4_unicode_ci`
  ENGINE = InnoDB
  ROW_FORMAT = DYNAMIC;");

        $this->addSql("ALTER TABLE `{$this->pointsTableName}` ADD group_id INT UNSIGNED DEFAULT NULL");
        $this->addSql("ALTER TABLE `{$this->pointTriggersTableName}` ADD group_id INT UNSIGNED DEFAULT NULL");
        $this->addSql("ALTER TABLE `{$this->leadPointsChangeLogTableName}` ADD group_id INT UNSIGNED DEFAULT NULL");

        $this->addSql("ALTER TABLE `{$this->contactScoreTableName}` ADD CONSTRAINT `{$this->contactScoreContactFk}` FOREIGN KEY (`contact_id`) REFERENCES `{$this->contactTableName}` (`id`) ON DELETE CASCADE");
        $this->addSql("ALTER TABLE `{$this->contactScoreTableName}` ADD CONSTRAINT `{$this->contactScoreGroupFk}` FOREIGN KEY (`group_id`) REFERENCES `{$this->groupTableName}` (`id`) ON DELETE CASCADE");

        $this->addSql("ALTER TABLE `{$this->pointsTableName}` ADD CONSTRAINT `{$this->pointsGroupFk}` FOREIGN KEY (`group_id`) REFERENCES `{$this->groupTableName}` (`id`) ON DELETE CASCADE");
        $this->addSql("ALTER TABLE `{$this->pointTriggersTableName}` ADD CONSTRAINT `{$this->pointTriggersGroupFk}` FOREIGN KEY (`group_id`) REFERENCES `{$this->groupTableName}` (`id`) ON DELETE CASCADE");
        $this->addSql("ALTER TABLE `{$this->leadPointsChangeLogTableName}` ADD CONSTRAINT `{$this->leadPointsChangeLogGroupFk}` FOREIGN KEY (`group_id`) REFERENCES `{$this->groupTableName}` (`id`) ON DELETE CASCADE");
    }

    public function down(Schema $schema): void
    {
        $this->initTableNames();

        $this->addSql("ALTER TABLE `{$this->contactScoreTableName}` DROP FOREIGN KEY `{$this->contactScoreContactFk}`");
        $this->addSql("ALTER TABLE `{$this->contactScoreTableName}` DROP FOREIGN KEY `{$this->contactScoreGroupFk}`");

        $this->addSql("ALTER TABLE `{$this->pointsTableName}` DROP FOREIGN KEY `{$this->pointsGroupFk}`");
        $this->addSql("ALTER TABLE `{$this->pointTriggersTableName}` DROP FOREIGN KEY `{$this->pointTriggersGroupFk}`");
        $this->addSql("ALTER TABLE `{$this->leadPointsChangeLogTableName}` DROP FOREIGN KEY `{$this->leadPointsChangeLogGroupFk}`");

        $this->addSql("ALTER TABLE `{$this->pointsTableName}` DROP group_id");
        $this->addSql("ALTER TABLE `{$this->pointTriggersTableName}` DROP group_id");
        $this->addSql("ALTER TABLE `{$this->leadPointsChangeLogTableName}` DROP group_id");

        $this->addSql("DROP TABLE {$this->contactScoreTableName}");
        $this->addSql("DROP TABLE {$this->groupTableName}");
    }

    private function generateTableName(string $tableName): string
    {
        return "{$this->prefix}$tableName";
    }

    private function assertTableDoesNotExist(string $tableName): void
    {
        $this->skipAssertion(
            fn (Schema $schema) => $schema->hasTable("{$tableName}"),
            "Table {$tableName} already exists"
        );
    }

    private function assertColumnDoesNotExist(string $tableName, string $columnName): void
    {
        $this->skipAssertion(
            fn (Schema $schema) => $schema->getTable("{$tableName}")->hasColumn($columnName),
            "Column {$tableName}.{$columnName} already exists"
        );
    }

    private function assertIndexDoesNotExist(string $tableName, string $indexName): void
    {
        $this->skipAssertion(
            fn (Schema $schema) => $schema->getTable("{$tableName}")->hasIndex($indexName),
            "Index {$indexName} already exists in {$tableName} table"
        );
    }
}
