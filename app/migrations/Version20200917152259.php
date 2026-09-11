<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\TextType;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;
use Mautic\CoreBundle\Doctrine\DatabasePlatform;

final class Version20200917152259 extends AbstractMauticMigration
{
    private string $table = 'lead_fields';

    /**
     * @throws SkipMigration
     */
    public function preUp(Schema $schema): void
    {
        $table = $schema->getTable($this->getTableName());

        $defaultValueColumn = $table->getColumn('default_value');

        // Skip if the column is already TEXT / LONGTEXT (platform-dependent)
        $currentType = $defaultValueColumn->getType();

        // MySQL uses TextType for LONGTEXT, PostgreSQL uses TextType for text
        if ($currentType instanceof TextType) {
            throw new SkipMigration('default_value is already the correct type (TEXT/LONGTEXT).');
        }
    }

    public function up(Schema $schema): void
    {
        $platform  = $this->connection->getDatabasePlatform();
        $tableName = $this->getTableName();

        if (DatabasePlatform::isPostgreSQL($platform)) {
            // PostgreSQL: change to text (equivalent of LONGTEXT)
            // Using USING clause is not needed here because VARCHAR → text is safe
            $this->addSql("ALTER TABLE {$tableName} ALTER COLUMN default_value TYPE text");
            // Make sure it's nullable with no default (if needed)
            $this->addSql("ALTER TABLE {$tableName} ALTER COLUMN default_value DROP DEFAULT");
            $this->addSql("ALTER TABLE {$tableName} ALTER COLUMN default_value DROP NOT NULL");
        } else {
            // MySQL / MariaDB: original syntax (LONGTEXT NULL DEFAULT NULL)
            $this->addSql("ALTER TABLE {$tableName} MODIFY default_value LONGTEXT NULL DEFAULT NULL");
        }
    }

    public function down(Schema $schema): void
    {
        $platform  = $this->connection->getDatabasePlatform();
        $tableName = $this->getTableName();

        if (DatabasePlatform::isPostgreSQL($platform)) {
            // Revert to varchar(191) – may truncate data if longer text exists!
            $this->addSql("ALTER TABLE {$tableName} ALTER COLUMN default_value TYPE varchar(191)");
        } else {
            $this->addSql("ALTER TABLE {$tableName} MODIFY default_value VARCHAR(191) NULL DEFAULT NULL");
        }
    }

    private function getTableName(): string
    {
        return $this->prefix.$this->table;
    }
}
