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
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Model\RoleModel;

final class Version20211209022550 extends AbstractMauticMigration
{
    public function postUp(Schema $schema): void
    {
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

            $leadPermission = $rawPermissions['lead:leads'] ?? [];
            $listPermission = $rawPermissions['lead:lists'] ?? [];

            if (empty($leadPermission) && empty($listPermission)) {
                continue;
            }

            // Map all leads permission to list.
            $newPermissions = $leadPermission;

            // If lead has viewown permission, then update
            if (in_array('viewown', $leadPermission)) {
                $newPermissions[] = 'create';
            }

            $rawPermissions['lead:lists'] = array_flip(array_flip($newPermissions));

            $model->setRolePermissions($role, $rawPermissions);
            $model->saveEntity($role);
        }
    }
}
