<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;
use Mautic\UserBundle\Entity\Permission;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Model\RoleModel;

final class Version20240712094201 extends AbstractMauticMigration
{
    private EntityManagerInterface $entityManager;

    /**
     * @throws \Exception
     */
    public function postUp(Schema $schema): void
    {
        $this->entityManager = $this->container->get('doctrine.orm.entity_manager');

        /** @var RoleModel $model */
        $model = $this->container->get('mautic.model.factory')->getModel('user.role');

        // Get all non-admin roles.
        $roles = $model->getEntities([
            'orderBy'    => 'r.id',
            'orderByDir' => 'ASC',
            'filter'     => [
                'where' => [
                    [
                        'col'  => 'r.isAdmin',
                        'expr' => 'eq',
                        'val'  => 0,
                    ],
                ],
            ],
        ]);

        /** @var Role $role */
        foreach ($roles as $role) {
            $rawPermissions = $role->getRawPermissions();

            if (empty($rawPermissions)) {
                continue;
            }

            $this->updatePermissions($role, $rawPermissions, 'lead', 'lead:export');
            $this->updatePermissions($role, $rawPermissions, 'form', 'form:export');
            $this->updatePermissions($role, $rawPermissions, 'report', 'report:export');
        }
    }

    /**
     * @param array<string, mixed> $rawPermissions
     *
     * @throws \Exception
     */
    private function updatePermissions(Role $role, array $rawPermissions, string $bundle, string $permissionKey): void
    {
        $exports = $rawPermissions[$permissionKey] ?? null;

        if (empty($exports)) {
            $this->setBitwise($bundle, $role, $rawPermissions);

            return;
        }

        $newExportPermissions   = $exports;
        $newExportPermissions[] = 'notAnonymize';

        foreach ($role->getPermissions()->getIterator() as $permission) {
            if ('export' !== $permission->getName() || $permission->getBundle() !== $bundle) {
                continue;
            }
            if (in_array('notAnonymize', $exports)) {
                continue;
            }

            $bit = $this->getPermissionBitwise($newExportPermissions);
            $permission->setBitwise($bit);
            $this->entityManager->persist($permission);
            $this->entityManager->flush();
        }
    }

    /**
     * @param array<string, array<int, string>> $rawPermissions
     *
     * @throws \Exception
     */
    private function setBitwise(string $bundle, Role $role, array $rawPermissions): void
    {
        $permission = new Permission();
        $permission->setBundle($bundle);
        $permission->setName('export');
        $permission->setBitwise(2);
        $this->entityManager->persist($permission);
        $role->addPermission($permission);
        $role->setRawPermissions($rawPermissions);
        $this->entityManager->persist($role);
        $this->entityManager->flush();
    }

    /**
     * @param array<string> $perms
     */
    private function getPermissionBitwise(array $perms): int
    {
        $permBitwise = [
            'notAnonymize' => 2,
            'enable'       => 1024,
        ];

        $bit = 0;
        foreach ($perms as $perm) {
            $bit += $permBitwise[$perm];
        }

        return $bit;
    }
}
