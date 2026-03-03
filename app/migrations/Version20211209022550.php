<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\UserBundle\Entity\Permission;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Model\RoleModel;

final class Version20211209022550 extends AbstractMauticMigration
{
    public function postUp(Schema $schema): void
    {
        /** @var RoleModel $model */
        $model = $this->container->get(ModelFactory::class)->getModel('user.role');

        // Get all non admin roles.
        $roles = $model->getEntities([
            'orderBy'       => 'r.id',
            'orderByDir'    => 'ASC',
            'filter'        => [
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

            $leadPermission = $rawPermissions['lead:leads'] ?? [];
            $listPermission = $rawPermissions['lead:lists'] ?? [];

            if (empty($leadPermission) && empty($listPermission)) {
                continue;
            }

            // Map all leads permission to list.
            $newPermissions = $leadPermission;

            if (!in_array('full', $newPermissions)) {
                // If lead has viewown permission, then add create permission for list.
                if (in_array('viewown', $leadPermission)) {
                    $newPermissions[] = 'create';
                }

                // Add the list related permission.
                foreach ($listPermission as $perm) {
                    $newPermissions[] = $perm;
                }
            }

            $perms = array_unique($newPermissions);

            $rawPermissions['lead:lists'] = $perms;

            $bit = $this->getPermissionBitwise($perms);

            // We have to get the segment permission to update the bitwise value.
            // The rest of the permission will stay as-is.
            $this->setBitwise($role, $bit, $rawPermissions);
        }
    }

    /**
     * @param string[] $perms
     */
    private function getPermissionBitwise(array $perms): int
    {
        $permBitwise = [
            'viewown'     => 2,
            'viewother'   => 4,
            'editown'     => 8,
            'editother'   => 16,
            'create'      => 32,
            'deleteown'   => 64,
            'deleteother' => 128,
            'full'        => 1024,
        ];

        $bit = 0;
        foreach ($perms as $perm) {
            $bit += $permBitwise[$perm];
        }

        return $bit;
    }

    /**
     * @param mixed[] $rawPermissions
     */
    private function setBitwise(Role $role, int $bit, array $rawPermissions): void
    {
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        \assert($entityManager instanceof EntityManagerInterface);

        $isPresent = false;
        /** @var Permission $permission */
        foreach ($role->getPermissions()->getIterator() as $permission) {
            if ('lists' !== $permission->getName()) {
                continue;
            }
            $isPresent = true;

            $permission->setBitwise($bit);
            $entityManager->persist($permission);
            break;
        }

        if (!$isPresent) {
            $permission = new Permission();
            $permission->setBundle('lead');
            $permission->setName('lists');
            $permission->setBitwise($bit);
            $entityManager->persist($permission);

            $role->addPermission($permission);
        }

        $role->setRawPermissions($rawPermissions);
        $entityManager->persist($role);
        $entityManager->flush();
    }
}
