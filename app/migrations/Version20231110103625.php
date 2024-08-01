<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20231110103625 extends AbstractMauticMigration
{
    protected const TABLE_NAME = 'roles';

    public function up(Schema $schema): void
    {
        $sql            = sprintf('SELECT id, readable_permissions FROM %s WHERE is_admin != 1', $this->getPrefixedTableName());
        $results        = $this->connection->executeQuery($sql)->fetchAllAssociative();
        $updatedRecords = 0;

        $addPermissions = [];
        foreach ($results as $row) {
            $permissionsArray = unserialize($row['readable_permissions']);
            // Add permissions if not exists
            $permissionsToAdd = ['lead:export', 'form:export', 'report:export'];

            foreach ($permissionsToAdd as $permission) {
                if (!isset($permissionsArray[$permission])) {
                    $permissionsArray[$permission]           = ['enable'];
                    $addPermissions[$row['id']][$permission] = 1024;
                }
            }

            $permissionsString = serialize($permissionsArray);

            $updateSql = sprintf('UPDATE %s SET readable_permissions = :permissions WHERE id = :id', $this->getPrefixedTableName());
            $stmt      = $this->connection->prepare($updateSql);
            $stmt->bindValue('permissions', $permissionsString, \PDO::PARAM_STR);
            $stmt->bindValue('id', $row['id'], \PDO::PARAM_INT);
            $updatedRecords += $stmt->executeStatement();

            foreach ($addPermissions as $roleId => $permissionsToAdd) {
                foreach ($permissionsToAdd as $permissionToAdd => $bitwise) {
                    $sql             = sprintf('INSERT IGNORE  INTO %s (role_id, bundle, name, bitwise) VALUES (:role_id, :bundle, :name, :bitwise)', $this->prefix.'permissions');
                    $stmt            = $this->connection->prepare($sql);
                    $permissionArray = explode(':', $permissionToAdd);
                    $stmt->bindValue('role_id', $row['id'], \PDO::PARAM_INT);
                    $stmt->bindValue('bundle', $permissionArray[0], \PDO::PARAM_STR);
                    $stmt->bindValue('name', $permissionArray[1], \PDO::PARAM_STR);
                    $stmt->bindValue('bitwise', $bitwise, \PDO::PARAM_INT);
                    $stmt->executeStatement();
                }
            }
        }

        $this->write(sprintf('<comment>%s record(s) have been updated successfully.</comment>', $updatedRecords));
    }
}
