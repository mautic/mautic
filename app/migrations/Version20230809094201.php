<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;
use Mautic\UserBundle\Entity\Permission;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Model\RoleModel;

final class Version20230809094201 extends AbstractMauticMigration
{
    private EntityManagerInterface $entityManager;


    public function postUp(Schema $schema): void
    {
        $this->entityManager = $this->container->get('doctrine.orm.entity_manager');

        /** @var RoleModel $model */
        $model = $this->container->get('mautic.model.factory')->getModel('user.role');

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

            $leadExports = $rawPermissions['lead:exports'];
            if (!empty($leadExports)) {
                continue;
            }

            $this->setBitwise($role, 1024, $rawPermissions);
        }
    }

    private function setBitwise(Role $role, int $bit, array $rawPermissions): void
    {
        $permission = new Permission();
        $permission->setBundle('lead');
        $permission->setName('exports');
        $permission->setBitwise($bit);
        $this->entityManager->persist($permission);

        $role->addPermission($permission);
        $role->setRawPermissions($rawPermissions);

        $this->entityManager->persist($role);
        $this->entityManager->flush();
    }
}
