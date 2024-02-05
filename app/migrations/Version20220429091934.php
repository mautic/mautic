<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

class Version20220429091934 extends PreUpAssertionMigration
{
    private const SIGNED   = 'SIGNED';

    private const UNSIGNED = 'UNSIGNED';

    protected function preUpAssertions(): void
    {
        $this->skipAssertion(function (Schema $schema) {
            return $schema->hasTable("{$this->prefix}contact_export_scheduler");
        }, sprintf('Table %s already exists', "{$this->prefix}contact_export_scheduler"));
    }

    public function up(Schema $schema): void
    {
        $userIdFK = $this->generatePropertyName('users', 'fk', ['user_id']);

        $contactExportSchedulerTableName = "{$this->prefix}contact_export_scheduler";
        $usersTableName                  = "{$this->prefix}users";

        $usersIdDataType = $this->getColumnDataType($schema->getTable($usersTableName), 'id');

        $this->addSql(
            "# Creating table {$this->prefix}contact_export_scheduler
            # ------------------------------------------------------------
            CREATE TABLE {$contactExportSchedulerTableName} (
                id INT AUTO_INCREMENT NOT NULL,
                user_id INT {$usersIdDataType} NOT NULL,
                scheduled_datetime DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                data LONGTEXT DEFAULT NULL COMMENT '(DC2Type:array)',
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;"
        );

        $this->addSql("ALTER TABLE {$contactExportSchedulerTableName} ADD CONSTRAINT {$userIdFK} FOREIGN KEY (user_id) REFERENCES $usersTableName (id)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            "# Dropping table {$this->prefix}contact_export_scheduler
            # ------------------------------------------------------------
            DROP TABLE {$this->prefix}contact_export_scheduler"
        );
    }

    /**
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    private function getColumnDataType(Table $table, string $columnName): string
    {
        $column = $table->getColumn($columnName);

        return $column->getUnsigned() ? self::UNSIGNED : self::SIGNED;
    }
}
