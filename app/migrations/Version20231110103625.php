<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;
use Mautic\CoreBundle\Doctrine\DatabasePlatform;

final class Version20231110103625 extends AbstractMauticMigration
{
    protected const TABLE_NAME = 'roles';

    private const PERMISSIONS_TO_ADD = [
        'lead:export'   => 1024,
        'form:export'   => 1024,
        'report:export' => 1024,
    ];

    public function up(Schema $schema): void
    {
        $rolesTable       = $this->prefix.self::TABLE_NAME;
        $permissionsTable = $this->prefix.'permissions';

        $platform   = $this->connection->getDatabasePlatform();

        if (DatabasePlatform::isPostgreSQL($platform)) {
            $sequenceName = $permissionsTable.'_id_seq';
            $nextval      = "nextval('{$sequenceName}')";

            $insertColumns = '(id, role_id, bundle, name, bitwise)';
            $insertValues  = "{$nextval}, :role_id, :bundle, :name, :bitwise";
        } else {
            $insertColumns = '(role_id, bundle, name, bitwise)';
            $insertValues  = ':role_id, :bundle, :name, :bitwise';
        }

        $insertSql = "INSERT INTO {$permissionsTable} {$insertColumns} VALUES ({$insertValues})";

        // Select all non-admin roles
        $selectSql = "SELECT id, readable_permissions FROM {$rolesTable} WHERE is_admin != :is_admin";
        $results   = $this->connection->executeQuery(
            $selectSql,
            ['is_admin' => true], // assuming is_admin is boolean, use true/false
            ['is_admin' => \PDO::PARAM_BOOL]
        )->fetchAllAssociative();

        $toInsert       = [];
        $updatedRecords = 0;

        foreach ($results as $row) {
            $roleId           = (int) $row['id'];
            $readable         = $row['readable_permissions'];
            $permissionsArray = $readable ? @unserialize($readable, ['allowed_classes' => false]) : [];
            if (false === $permissionsArray) {
                $permissionsArray = [];
            }

            $changed = false;
            foreach (self::PERMISSIONS_TO_ADD as $permission => $bitwise) {
                if (!array_key_exists($permission, $permissionsArray)) {
                    $permissionsArray[$permission] = ['enable'];
                    $changed                       = true;

                    [$bundle, $name] = explode(':', $permission);

                    $toInsert[] = [
                        'role_id' => $roleId,
                        'bundle'  => $bundle,
                        'name'    => $name,
                        'bitwise' => $bitwise,
                    ];
                }
            }

            // Update readable_permissions if any new permissions were added (more precise than always updating)
            if ($changed) {
                $newReadable = serialize($permissionsArray);

                $updateSql = "UPDATE {$rolesTable} SET readable_permissions = :permissions WHERE id = :id";
                $stmt      = $this->connection->prepare($updateSql);
                $stmt->bindValue('permissions', $newReadable);
                $stmt->bindValue('id', $roleId, \PDO::PARAM_INT);
                $updatedRecords += $stmt->executeStatement();
            }
        }

        // Insert missing permissions (idempotent)
        foreach ($toInsert as $data) {
            $checkSql = "SELECT 1 FROM {$permissionsTable}
                         WHERE role_id = :role_id
                           AND bundle = :bundle
                           AND name = :name
                         LIMIT 1";

            $exists = (bool) $this->connection->fetchOne(
                $checkSql,
                $data,
                ['role_id' => \PDO::PARAM_INT, 'bundle' => \PDO::PARAM_STR, 'name' => \PDO::PARAM_STR]
            );

            if (!$exists) {
                $stmt = $this->connection->prepare($insertSql);
                $stmt->bindValue('role_id', $data['role_id'], \PDO::PARAM_INT);
                $stmt->bindValue('bundle', $data['bundle'], \PDO::PARAM_STR);
                $stmt->bindValue('name', $data['name'], \PDO::PARAM_STR);
                $stmt->bindValue('bitwise', $data['bitwise'], \PDO::PARAM_INT);
                // No bind for id if PostgreSQL (handled by nextval)
                $stmt->executeStatement();
            }
        }

        if ($updatedRecords > 0) {
            $this->write(sprintf('<comment>%d role record(s) have been updated successfully.</comment>', $updatedRecords));
        } else {
            $this->write('<comment>No roles needed updates.</comment>');
        }
    }
}
