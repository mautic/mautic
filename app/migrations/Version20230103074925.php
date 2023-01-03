<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;
use Mautic\PointBundle\Entity\League;
use Mautic\PointBundle\Entity\LeagueCompanyScore;
use Mautic\PointBundle\Entity\LeagueContactScore;

final class Version20230103074925 extends AbstractMauticMigration
{
    /**
     * @throws SkipMigration
     */
    public function preUp(Schema $schema): void
    {
        $shouldRunMigration = !$schema->hasTable($this->generateTableName(League::TABLE_NAME));

        if (!$shouldRunMigration) {
            throw new SkipMigration('Schema includes this migration');
        }
    }

    public function up(Schema $schema): void
    {
        $leagueTableName       = $this->generateTableName(League::TABLE_NAME);
        $contactScoreTableName = $this->generateTableName(LeagueContactScore::TABLE_NAME);
        $companyScoreTableName = $this->generateTableName(LeagueCompanyScore::TABLE_NAME);
        $contactTableName      = $this->generateTableName('leads');
        $companyTableName      = $this->generateTableName('companies');

        $contactScoreContactFk = $this->generatePropertyName($contactScoreTableName, 'fk', ['contact_id']);
        $contactScoreLeagueFk  = $this->generatePropertyName($contactScoreTableName, 'fk', ['league_id']);

        $companyScoreCompanyFk = $this->generatePropertyName($companyScoreTableName, 'fk', ['company_id']);
        $companyScoreLeagueFk  = $this->generatePropertyName($companyScoreTableName, 'fk', ['league_id']);

        $this->addSql("CREATE TABLE `{$leagueTableName}`
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
    `league_id`           INT UNSIGNED    NOT NULL,
    `is_published`        TINYINT(1)      NOT NULL,
    `date_added`          DATETIME     DEFAULT NULL,
    `created_by`          INT          DEFAULT NULL,
    `created_by_user`     VARCHAR(191) DEFAULT NULL,
    `date_modified`       DATETIME     DEFAULT NULL,
    `modified_by`         INT          DEFAULT NULL,
    `modified_by_user`    VARCHAR(191) DEFAULT NULL,
    `checked_out`         DATETIME     DEFAULT NULL,
    `checked_out_by`      INT          DEFAULT NULL,
    `checked_out_by_user` VARCHAR(191) DEFAULT NULL,
    `score`               INT             NOT NULL,
    PRIMARY KEY (`contact_id`, `league_id`)
) DEFAULT CHARACTER SET utf8mb4
  COLLATE `utf8mb4_unicode_ci`
  ENGINE = InnoDB
  ROW_FORMAT = DYNAMIC;");

        $this->addSql("CREATE TABLE `{$companyScoreTableName}`
(
    `company_id`          INT          NOT NULL,
    `league_id`           INT UNSIGNED NOT NULL,
    `is_published`        TINYINT(1)   NOT NULL,
    `date_added`          DATETIME     DEFAULT NULL,
    `created_by`          INT          DEFAULT NULL,
    `created_by_user`     VARCHAR(191) DEFAULT NULL,
    `date_modified`       DATETIME     DEFAULT NULL,
    `modified_by`         INT          DEFAULT NULL,
    `modified_by_user`    VARCHAR(191) DEFAULT NULL,
    `checked_out`         DATETIME     DEFAULT NULL,
    `checked_out_by`      INT          DEFAULT NULL,
    `checked_out_by_user` VARCHAR(191) DEFAULT NULL,
    `score`               INT          NOT NULL,
    PRIMARY KEY (`company_id`, `league_id`)
) DEFAULT CHARACTER SET utf8mb4
  COLLATE `utf8mb4_unicode_ci`
  ENGINE = InnoDB
  ROW_FORMAT = DYNAMIC;");

        $this->addSql("ALTER TABLE `{$contactScoreTableName}` ADD CONSTRAINT `{$contactScoreContactFk}` FOREIGN KEY (`contact_id`) REFERENCES `{$contactTableName}` (`id`) ON DELETE CASCADE");
        $this->addSql("ALTER TABLE `{$contactScoreTableName}` ADD CONSTRAINT `{$contactScoreLeagueFk}` FOREIGN KEY (`league_id`) REFERENCES `{$leagueTableName}` (`id`) ON DELETE CASCADE");

        $this->addSql("ALTER TABLE `{$companyScoreTableName}` ADD CONSTRAINT `{$companyScoreCompanyFk}` FOREIGN KEY (`company_id`) REFERENCES `{$companyTableName}` (`id`) ON DELETE CASCADE");
        $this->addSql("ALTER TABLE `{$companyScoreTableName}` ADD CONSTRAINT `{$companyScoreLeagueFk}` FOREIGN KEY (`league_id`) REFERENCES `{$leagueTableName}` (`id`) ON DELETE CASCADE");
    }

    public function down(Schema $schema): void
    {
        $leagueTableName       = $this->generateTableName(League::TABLE_NAME);
        $contactScoreTableName = $this->generateTableName(LeagueContactScore::TABLE_NAME);
        $companyScoreTableName = $this->generateTableName(LeagueCompanyScore::TABLE_NAME);

        $this->addSql("DROP TABLE {$companyScoreTableName}");
        $this->addSql("DROP TABLE {$contactScoreTableName}");
        $this->addSql("DROP TABLE {$leagueTableName}");
    }

    private function generateTableName(string $tableName): string
    {
        return "{$this->prefix}$tableName";
    }
}
