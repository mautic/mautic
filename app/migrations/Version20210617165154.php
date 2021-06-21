<?php

declare(strict_types=1);

/*
 * @copyright   2021 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        https://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;
use Mautic\UserBundle\Model\RoleModel;
use Mautic\UserBundle\Model\UserModel;

final class Version20210617165154 extends AbstractMauticMigration
{
    /**
     * @var array|false
     */
    private $rowsToMigrate;

    /**
     * @throws SkipMigration
     */
    public function preUp(Schema $schema): void
    {
        $this->fetchRolesToMigrate();

        if (false === $this->rowsToMigrate) {
            throw new SkipMigration('No data to migrate');
        }
    }

    public function up(Schema $schema): void
    {
        if (false !== $this->rowsToMigrate) {
            $this->migrateRoles();
        }
    }

    private function fetchRolesToMigrate()
    {
        $sql = <<<SQL
            SELECT id,readable_permissions
            FROM {$this->prefix}roles
            WHERE
                is_admin = 0
SQL;

        $stmt                = $this->connection->prepare($sql);
        $result              = $stmt->executeQuery();
        $this->rowsToMigrate = $result->fetchAllAssociative();
    }

    private function migrateRoles()
    {
        foreach ($this->rowsToMigrate as $rowToMigrate) {
            $permissions         = unserialize($rowToMigrate['readable_permissions']);
            $leadListPermissions = ($permissions['lead:lists'] ?? []);

            if (!in_array('full', $leadListPermissions) && !in_array('create', $leadListPermissions)) {
                $permissions['lead:lists'][] = 'create';
                $params                      = [
                    'id'          => $rowToMigrate['id'],
                    'permissions' => serialize($permissions),
                ];

                $this->addSql($this->getUpdateSql(), $params);
                $this->setPermission($rowToMigrate['id'], $permissions);
            }
        }
    }

    private function getUpdateSql(): string
    {
        return <<<SQL
            UPDATE {$this->prefix}roles
            SET readable_permissions = :permissions
            WHERE id = :id
SQL;
    }

    /**
     * @param $id
     */
    private function setPermission($id, array $permissions)
    {
        /** @var RoleModel $roleModel */
        $roleModel = $this->container->get('mautic.user.model.role');
        $role      = $roleModel->getEntity($id);
        $roleModel->setRolePermissions($role, $permissions);
        $roleModel->saveEntity($role);
    }
}
