<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\ParameterType;
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
            $stmt->bindValue('permissions', $permissionsString, ParameterType::STRING);
            $stmt->bindValue('id', $row['id'], ParameterType::INTEGER);
            $updatedRecords += $stmt->executeStatement();

            foreach ($addPermissions as $permissionsToAdd) {
                foreach ($permissionsToAdd as $permissionToAdd => $bitwise) {
                    $sql             = sprintf('INSERT IGNORE  INTO %s (role_id, bundle, name, bitwise) VALUES (:role_id, :bundle, :name, :bitwise)', $this->prefix.'permissions');
                    $stmt            = $this->connection->prepare($sql);
                    $permissionArray = explode(':', $permissionToAdd);
                    $stmt->bindValue('role_id', $row['id'], ParameterType::INTEGER);
                    $stmt->bindValue('bundle', $permissionArray[0], ParameterType::STRING);
                    $stmt->bindValue('name', $permissionArray[1], ParameterType::STRING);
                    $stmt->bindValue('bitwise', $bitwise, ParameterType::INTEGER);
                    $stmt->executeStatement();
                }
            }
        }

        $this->write(sprintf('<comment>%s record(s) have been updated successfully.</comment>', $updatedRecords));
    }
}
