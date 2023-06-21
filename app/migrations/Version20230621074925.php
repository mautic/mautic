<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;
use Mautic\PointBundle\Entity\Group;
use Mautic\PointBundle\Entity\GroupContactScore;

final class Version20230621074925 extends AbstractMauticMigration
{
    /**
     * @throws SkipMigration
     */
    public function preUp(Schema $schema): void
    {
        $shouldRunMigration = !$schema->hasTable($this->generateTableName(Group::TABLE_NAME));

        if (!$shouldRunMigration) {
            throw new SkipMigration('Schema includes this migration');
        }
    }

    public function up(Schema $schema): void
    {
        $groupTableName              = $this->generateTableName(Group::TABLE_NAME);
        $contactScoreTableName        = $this->generateTableName(GroupContactScore::TABLE_NAME);
        $contactTableName             = $this->generateTableName('leads');
        $pointsTableName              = $this->generateTableName('points');
        $pointTriggersTableName       = $this->generateTableName('point_triggers');
        $leadPointsChangeLogTableName = $this->generateTableName('lead_points_change_log');

        $contactScoreContactFk = $this->generatePropertyName($contactScoreTableName, 'fk', ['contact_id']);
        $contactScoreGroupFk  = $this->generatePropertyName($contactScoreTableName, 'fk', ['group_id']);

        $pointsGroupFk              = $this->generatePropertyName($pointsTableName, 'fk', ['group_id']);
        $pointTriggersGroupFk       = $this->generatePropertyName($pointTriggersTableName, 'fk', ['group_id']);
        $leadPointsChangeLogGroupFk = $this->generatePropertyName($leadPointsChangeLogTableName, 'fk', ['group_id']);

        $this->addSql("CREATE TABLE `{$groupTableName}`
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

        $this->addSql("CREATE TABLE `{$contactScoreTableName}`
(
    `contact_id`          BIGINT UNSIGNED NOT NULL,
    `group_id`           INT UNSIGNED    NOT NULL,
    `score`               INT             NOT NULL,
    PRIMARY KEY (`contact_id`, `group_id`)
) DEFAULT CHARACTER SET utf8mb4
  COLLATE `utf8mb4_unicode_ci`
  ENGINE = InnoDB
  ROW_FORMAT = DYNAMIC;");

        $this->addSql("ALTER TABLE `{$pointsTableName}` ADD group_id INT UNSIGNED DEFAULT NULL");
        $this->addSql("ALTER TABLE `{$pointTriggersTableName}` ADD group_id INT UNSIGNED DEFAULT NULL");
        $this->addSql("ALTER TABLE `{$leadPointsChangeLogTableName}` ADD group_id INT UNSIGNED DEFAULT NULL");

        $this->addSql("ALTER TABLE `{$contactScoreTableName}` ADD CONSTRAINT `{$contactScoreContactFk}` FOREIGN KEY (`contact_id`) REFERENCES `{$contactTableName}` (`id`) ON DELETE CASCADE");
        $this->addSql("ALTER TABLE `{$contactScoreTableName}` ADD CONSTRAINT `{$contactScoreGroupFk}` FOREIGN KEY (`group_id`) REFERENCES `{$groupTableName}` (`id`) ON DELETE CASCADE");

        $this->addSql("ALTER TABLE `{$pointsTableName}` ADD CONSTRAINT `{$pointsGroupFk}` FOREIGN KEY (`group_id`) REFERENCES `{$groupTableName}` (`id`) ON DELETE CASCADE");
        $this->addSql("ALTER TABLE `{$pointTriggersTableName}` ADD CONSTRAINT `{$pointTriggersGroupFk}` FOREIGN KEY (`group_id`) REFERENCES `{$groupTableName}` (`id`) ON DELETE CASCADE");
        $this->addSql("ALTER TABLE `{$leadPointsChangeLogTableName}` ADD CONSTRAINT `{$leadPointsChangeLogGroupFk}` FOREIGN KEY (`group_id`) REFERENCES `{$groupTableName}` (`id`) ON DELETE CASCADE");
    }

    public function down(Schema $schema): void
    {
        $pointsTableName              = $this->generateTableName('points');
        $pointTriggersTableName       = $this->generateTableName('point_triggers');
        $groupTableName              = $this->generateTableName(Group::TABLE_NAME);
        $contactScoreTableName        = $this->generateTableName(GroupContactScore::TABLE_NAME);
        $leadPointsChangeLogTableName = $this->generateTableName('lead_points_change_log');

        $contactScoreContactFk = $this->generatePropertyName($contactScoreTableName, 'fk', ['contact_id']);
        $contactScoreGroupFk  = $this->generatePropertyName($contactScoreTableName, 'fk', ['group_id']);

        $pointsGroupFk              = $this->generatePropertyName($pointsTableName, 'fk', ['group_id']);
        $pointTriggersGroupFk       = $this->generatePropertyName($pointTriggersTableName, 'fk', ['group_id']);
        $leadPointsChangeLogGroupFk = $this->generatePropertyName($leadPointsChangeLogTableName, 'fk', ['group_id']);

        $this->addSql("ALTER TABLE `{$contactScoreTableName}` DROP FOREIGN KEY `{$contactScoreContactFk}`");
        $this->addSql("ALTER TABLE `{$contactScoreTableName}` DROP FOREIGN KEY `{$contactScoreGroupFk}`");

        $this->addSql("ALTER TABLE `{$pointsTableName}` DROP FOREIGN KEY `{$pointsGroupFk}`");
        $this->addSql("ALTER TABLE `{$pointTriggersTableName}` DROP FOREIGN KEY `{$pointTriggersGroupFk}`");
        $this->addSql("ALTER TABLE `{$leadPointsChangeLogTableName}` DROP FOREIGN KEY `{$leadPointsChangeLogGroupFk}`");

        $this->addSql("ALTER TABLE `{$pointsTableName}` DROP group_id");
        $this->addSql("ALTER TABLE `{$pointTriggersTableName}` DROP group_id");
        $this->addSql("ALTER TABLE `{$leadPointsChangeLogTableName}` DROP group_id");

        $this->addSql("DROP TABLE {$contactScoreTableName}");
        $this->addSql("DROP TABLE {$groupTableName}");
    }

    private function generateTableName(string $tableName): string
    {
        return "{$this->prefix}$tableName";
    }
}
