<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;
use Mautic\UserBundle\Entity\Permission;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Model\RoleModel;
use Symfony\Component\Console\Input\ArgvInput;

final class Version20261015121426 extends AbstractMauticMigration
{
    public function postUp(Schema $schema): void
    {
        if ((new ArgvInput())->hasParameterOption('--dry-run')) {
            return;
        }

        $model = $this->container->get(RoleModel::class);

        // Get all non admin roles.
        $entries = $model->getEntities([
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

        foreach ($entries as $entry) {
            $role = $entry[0];
            \assert($role instanceof Role);

            $rawPermissions = $role->getRawPermissions() ?? [];

            if ([] === $rawPermissions) {
                continue;
            }

            $emailPermission = $rawPermissions['email:emails'] ?? [];
            $pagePermission  = $rawPermissions['page:pages'] ?? [];
            $assetPermission = $rawPermissions['asset:assets'] ?? [];
            $dwcPermission   = $rawPermissions['dynamiccontent:dynamiccontents'] ?? [];

            $grantedRelevantPermissions = array_unique(array_merge($emailPermission, $pagePermission, $assetPermission, $dwcPermission));
            $requiredPermissions        = ['editown', 'editother', 'create', 'full'];

            $hasFileManagerPermission = [] !== array_intersect($requiredPermissions, $grantedRelevantPermissions);

            if ($hasFileManagerPermission) {
                $this->setFullPermissionBitwise($role, $rawPermissions);
            }
        }
    }

    /**
     * @param mixed[] $rawPermissions
     */
    private function setFullPermissionBitwise(Role $role, array $rawPermissions): void
    {
        if (!empty($rawPermissions['core:file_manager'])) {
            return;
        }

        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        \assert($entityManager instanceof EntityManagerInterface);

        $permission = new Permission();
        $permission->setBundle('core');
        $permission->setName('file_manager');
        $permission->setBitwise(1024);
        $entityManager->persist($permission);

        $role->addPermission($permission);
        $rawPermissions['core:file_manager'] = ['full'];
        $role->setRawPermissions($rawPermissions);
        $entityManager->persist($role);

        $entityManager->flush();
    }
}
