<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Entity;

use Doctrine\ORM\Query;
use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * PermissionRepository.
 */
class PermissionRepository extends CommonRepository
{
    /**
     * Delete all permissions for a specific role.
     *
     * @param Role $role
     */
    public function purgeRolePermissions(Role $role)
    {
        $query = $this
            ->createQueryBuilder('p')
            ->delete('MauticUserBundle:Permission', 'p')
            ->where('p.role = :role')
            ->setParameter(':role', $role)
            ->getQuery();
        $query->execute();
    }

    /**
     * Retrieves array of permissions for a set role.  If $forForm, then the array will contain.
     *
     * @param Role $role
     * @param bool $forForm
     *
     * @return array
     */
    public function getPermissionsByRole(Role $role, $forForm = false)
    {
        $results = $this
            ->createQueryBuilder('p')
            ->where('p.role = :role')
            ->orderBy('p.bundle')
            ->setParameter(':role', $role)
            ->getQuery()
            ->useResultCache(false)
            ->getResult(Query::HYDRATE_ARRAY);

        //rearrange the array to meet needs
        $permissions = [];
        foreach ($results as $r) {
            if ($forForm) {
                $permissions[$r['bundle']][$r['id']] = [
                    'name'    => $r['name'],
                    'bitwise' => $r['bitwise'],
                ];
            } else {
                $permissions[$r['bundle']][$r['name']] = $r['bitwise'];
            }
        }

        return $permissions;
    }
}
