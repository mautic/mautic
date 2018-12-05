<?php

/*
 * @package     Mautic
 * @copyright   2018 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

class Version20181204000000 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        /** @var \Mautic\CoreBundle\Security\Permissions\CorePermissions $security */
        $security            = $this->container->get('mautic.security');
        $campaignPermissions = $security->getPermissionObject('campaign');

        $roles = $this->connection->createQueryBuilder()
            ->select('r.*')
            ->from(MAUTIC_TABLE_PREFIX.'roles', 'r')
            ->execute()
            ->fetchAll();

        foreach ($roles as $role) {
            $unserializedArray = unserialize($role['readable_permissions']);

            if (null !== $unserializedArray && isset($unserializedArray['campaign:campaigns'])) {
                $newPermissions = [];

                if (in_array('full', $unserializedArray['campaign:campaigns'])) {
                    $newPermissions[] = 'full';
                }

                if (in_array('view', $unserializedArray['campaign:campaigns'])) {
                    $newPermissions[] = 'viewown';
                    $newPermissions[] = 'viewother';
                }

                if (in_array('edit', $unserializedArray['campaign:campaigns'])) {
                    $newPermissions[] = 'editown';
                    $newPermissions[] = 'editother';
                }

                if (in_array('create', $unserializedArray['campaign:campaigns'])) {
                    $newPermissions[] = 'create';
                }

                if (in_array('delete', $unserializedArray['campaign:campaigns'])) {
                    $newPermissions[] = 'deleteown';
                    $newPermissions[] = 'deleteother';
                }

                if (in_array('publish', $unserializedArray['campaign:campaigns'])) {
                    $newPermissions[] = 'publishown';
                    $newPermissions[] = 'publishother';
                }

                $unserializedArray['campaign:campaigns'] = $newPermissions;
                $serializedArray                         = serialize($unserializedArray);

                $this->connection->update(MAUTIC_TABLE_PREFIX.'roles',
                    [
                        'readable_permissions'          => $serializedArray,
                    ],
                    [
                        'id' => $role['id'],
                    ]
                );

                $bit = 0;
                foreach ($newPermissions as $permission) {
                    $bit += $campaignPermissions->getValue('campaigns', $permission);
                }

                $this->connection->update(
                    MAUTIC_TABLE_PREFIX.'permissions',
                    [
                        'bitwise' => $bit,
                    ],
                    [
                        'role_id' => $role['id'],
                        'bundle'  => 'campaign',
                        'name'    => 'campaigns',
                    ]
                );
            }
        }
    }
}
