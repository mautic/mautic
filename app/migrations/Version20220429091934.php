<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

class Version20220429091934 extends PreUpAssertionMigration
{
    protected function preUpAssertions(): void
    {
        $this->skipAssertion(function (Schema $schema) {
            return $schema->hasTable("{$this->prefix}contact_export_scheduler");
        }, sprintf('Table %s already exists', "{$this->prefix}contact_export_scheduler"));
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            "# Creating table {$this->prefix}contact_export_scheduler
            # ------------------------------------------------------------
            CREATE TABLE {$this->prefix}contact_export_scheduler (
                id INT AUTO_INCREMENT NOT NULL,
                user_id INT UNSIGNED NOT NULL,
                scheduled_datetime DATE NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                data LONGTEXT DEFAULT NULL COMMENT '(DC2Type:array)',
                PRIMARY KEY(id),
                FOREIGN KEY (user_id) REFERENCES {$this->prefix}users (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;"
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            "# Dropping table {$this->prefix}contact_export_scheduler
            # ------------------------------------------------------------
            DROP TABLE {$this->prefix}contact_export_scheduler"
        );
    }
}
