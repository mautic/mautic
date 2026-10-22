<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20260616075543 extends PreUpAssertionMigration
{
    protected function preUpAssertions(): void
    {
        $this->skipAssertion(
            fn (Schema $schema): bool => $schema->hasTable("{$this->prefix}point_insights")
                && $schema->getTable("{$this->prefix}point_insights")->hasForeignKey('FK_9894AA2612469DE2'),
            "Table {$this->prefix}point_insights and foreign key FK_9894AA2612469DE2 already exist"
        );
    }

    public function up(Schema $schema): void
    {
        $categoryIdDataType = $this->getColumnTypeSignedOrUnsigned($schema, 'categories', 'id');
        $categoryIdType     = self::COLUMN_TYPE_UNSIGNED === $categoryIdDataType ? 'INT UNSIGNED' : 'INT';
        $tableName           = "{$this->prefix}point_insights";

        if ($schema->hasTable($tableName)) {
            $this->addSql("ALTER TABLE `{$tableName}` MODIFY `category_id` {$categoryIdType} DEFAULT NULL");
            $this->addSql("ALTER TABLE `{$tableName}` ADD CONSTRAINT FK_9894AA2612469DE2 FOREIGN KEY (`category_id`) REFERENCES `{$this->prefix}categories` (`id`) ON DELETE SET NULL");

            return;
        }

        $this->addSql("CREATE TABLE `{$tableName}` (
            `id`                  INT UNSIGNED AUTO_INCREMENT NOT NULL,
            `category_id`         {$categoryIdType} DEFAULT NULL,
            `is_published`        TINYINT(1) NOT NULL,
            `date_added`          DATETIME DEFAULT NULL,
            `created_by`          INT DEFAULT NULL,
            `created_by_user`     VARCHAR(191) DEFAULT NULL,
            `date_modified`       DATETIME DEFAULT NULL,
            `modified_by`         INT DEFAULT NULL,
            `modified_by_user`    VARCHAR(191) DEFAULT NULL,
            `checked_out`         DATETIME DEFAULT NULL,
            `checked_out_by`      INT DEFAULT NULL,
            `checked_out_by_user` VARCHAR(191) DEFAULT NULL,
            `name`                VARCHAR(191) NOT NULL,
            `description`         LONGTEXT DEFAULT NULL,
            `insight_type`        VARCHAR(191) NOT NULL,
            `insight_action`      VARCHAR(191) NOT NULL,
            `custom_field`        VARCHAR(191) DEFAULT NULL,
            `point_groups`        LONGTEXT NOT NULL COMMENT '(DC2Type:json)',
            INDEX IDX_9894AA2612469DE2 (`category_id`),
            PRIMARY KEY(`id`)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC");

        $this->addSql("ALTER TABLE `{$this->prefix}point_insights` ADD CONSTRAINT FK_9894AA2612469DE2 FOREIGN KEY (`category_id`) REFERENCES `{$this->prefix}categories` (`id`) ON DELETE SET NULL");
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable("{$this->prefix}point_insights")) {
            $table = $schema->getTable("{$this->prefix}point_insights");
            if ($table->hasForeignKey('FK_9894AA2612469DE2')) {
                $this->addSql("ALTER TABLE `{$this->prefix}point_insights` DROP FOREIGN KEY FK_9894AA2612469DE2");
            }
            $this->addSql("DROP TABLE `{$this->prefix}point_insights`");
        }
    }
}
