<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
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

        $this->assertForeignKeyDoesNotExist($this->contactScoreTableName, $this->contactScoreContactFk);
        $this->assertForeignKeyDoesNotExist($this->contactScoreTableName, $this->contactScoreGroupFk);
        $this->assertForeignKeyDoesNotExist($this->pointsTableName, $this->pointsGroupFk);
        $this->assertForeignKeyDoesNotExist($this->pointTriggersTableName, $this->pointTriggersGroupFk);
        $this->assertForeignKeyDoesNotExist($this->leadPointsChangeLogTableName, $this->leadPointsChangeLogGroupFk);
    }

    public function up(Schema $schema): void
    {
        $this->initTableNames();

        // Create point_groups table if it does not exist
        if (!$schema->hasTable($this->groupTableName)) {
            $groupTable = $schema->createTable($this->groupTableName);
            $groupTable->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
            $groupTable->addColumn('is_published', 'boolean', ['notnull' => true]);
            $groupTable->addColumn('date_added', 'datetime', ['notnull' => false]);
            $groupTable->addColumn('created_by', 'integer', ['notnull' => false]);
            $groupTable->addColumn('created_by_user', 'string', ['length' => 191, 'notnull' => false]);
            $groupTable->addColumn('date_modified', 'datetime', ['notnull' => false]);
            $groupTable->addColumn('modified_by', 'integer', ['notnull' => false]);
            $groupTable->addColumn('modified_by_user', 'string', ['length' => 191, 'notnull' => false]);
            $groupTable->addColumn('checked_out', 'datetime', ['notnull' => false]);
            $groupTable->addColumn('checked_out_by', 'integer', ['notnull' => false]);
            $groupTable->addColumn('checked_out_by_user', 'string', ['length' => 191, 'notnull' => false]);
            $groupTable->addColumn('name', 'string', ['length' => 191, 'notnull' => true]);
            $groupTable->addColumn('description', 'text', ['notnull' => false]);
            $groupTable->setPrimaryKey(['id']);
        }

        // Create point_group_contact_scores table if it does not exist
        if (!$schema->hasTable($this->contactScoreTableName)) {
            $contactScoreTable = $schema->createTable($this->contactScoreTableName);

            $contactTable = $schema->getTable($this->contactTableName);
            $idColumn     = $contactTable->getColumn('id');
            $typeName     = Type::getTypeRegistry()->lookupName($idColumn->getType());

            $contactScoreTable->addColumn('contact_id', $typeName, [
                'unsigned' => $idColumn->getUnsigned(),
                'notnull'  => true,
            ]);
            $contactScoreTable->addColumn('group_id', 'integer', ['unsigned' => true, 'notnull' => true]);
            $contactScoreTable->addColumn('score', 'integer', ['notnull' => true]);

            $contactScoreTable->setPrimaryKey(['contact_id', 'group_id']);
        }

        // Add foreign keys to point_group_contact_scores (idempotent, named)
        $contactScoreTable = $schema->getTable($this->contactScoreTableName);
        $groupTable        = $schema->getTable($this->groupTableName);
        $contactTable      = $schema->getTable($this->contactTableName);

        if (!$contactScoreTable->hasForeignKey($this->contactScoreContactFk)) {
            $contactScoreTable->addForeignKeyConstraint(
                $contactTable,
                ['contact_id'],
                ['id'],
                ['onDelete' => 'CASCADE'],
                $this->contactScoreContactFk
            );
        }

        if (!$contactScoreTable->hasForeignKey($this->contactScoreGroupFk)) {
            $contactScoreTable->addForeignKeyConstraint(
                $groupTable,
                ['group_id'],
                ['id'],
                ['onDelete' => 'CASCADE'],
                $this->contactScoreGroupFk
            );
        }

        // Add group_id column + named FK to points, point_triggers and lead_points_change_log (idempotent)
        $tables = [
            $this->pointsTableName              => $this->pointsGroupFk,
            $this->pointTriggersTableName       => $this->pointTriggersGroupFk,
            $this->leadPointsChangeLogTableName => $this->leadPointsChangeLogGroupFk,
        ];

        foreach ($tables as $tableName => $fkName) {
            $table = $schema->getTable($tableName);

            if (!$table->hasColumn('group_id')) {
                $table->addColumn('group_id', 'integer', ['unsigned' => true, 'notnull' => false]);
            }

            if (!$table->hasForeignKey($fkName)) {
                $table->addForeignKeyConstraint(
                    $groupTable,
                    ['group_id'],
                    ['id'],
                    ['onDelete' => 'CASCADE'],
                    $fkName
                );
            }
        }
    }

    public function down(Schema $schema): void
    {
        $this->initTableNames();

        // Remove FKs from point_group_contact_scores if they exist
        if ($schema->hasTable($this->contactScoreTableName)) {
            $contactScoreTable = $schema->getTable($this->contactScoreTableName);

            if ($contactScoreTable->hasForeignKey($this->contactScoreContactFk)) {
                $contactScoreTable->removeForeignKey($this->contactScoreContactFk);
            }

            if ($contactScoreTable->hasForeignKey($this->contactScoreGroupFk)) {
                $contactScoreTable->removeForeignKey($this->contactScoreGroupFk);
            }
        }

        // Remove FK and column from points, point_triggers and lead_points_change_log
        $tables = [
            $this->pointsTableName              => $this->pointsGroupFk,
            $this->pointTriggersTableName       => $this->pointTriggersGroupFk,
            $this->leadPointsChangeLogTableName => $this->leadPointsChangeLogGroupFk,
        ];

        foreach ($tables as $tableName => $fkName) {
            if ($schema->hasTable($tableName)) {
                $table = $schema->getTable($tableName);

                if ($table->hasForeignKey($fkName)) {
                    $table->removeForeignKey($fkName);
                }

                if ($table->hasColumn('group_id')) {
                    $table->dropColumn('group_id');
                }
            }
        }

        // Drop tables if they exist (contact scores first due to FK)
        if ($schema->hasTable($this->contactScoreTableName)) {
            $schema->dropTable($this->contactScoreTableName);
        }

        if ($schema->hasTable($this->groupTableName)) {
            $schema->dropTable($this->groupTableName);
        }
    }

    private function generateTableName(string $tableName): string
    {
        return "{$this->prefix}$tableName";
    }

    private function assertTableDoesNotExist(string $tableName): void
    {
        $this->skipAssertion(
            fn (Schema $schema) => $schema->hasTable($tableName),
            "Table {$tableName} already exists"
        );
    }

    private function assertColumnDoesNotExist(string $tableName, string $columnName): void
    {
        $this->skipAssertion(
            fn (Schema $schema) => $schema->getTable($tableName)->hasColumn($columnName),
            "Column {$tableName}.{$columnName} already exists"
        );
    }

    private function assertForeignKeyDoesNotExist(string $tableName, string $fkName): void
    {
        $this->skipAssertion(
            fn (Schema $schema) => $schema->getTable($tableName)->hasForeignKey($fkName),
            "Foreign key {$fkName} already exists in {$tableName} table"
        );
    }
}
