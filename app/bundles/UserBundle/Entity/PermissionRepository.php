<?php

namespace Mautic\UserBundle\Entity;

use Doctrine\ORM\Query;
use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * @extends CommonRepository<Permission>
 */
class PermissionRepository extends CommonRepository
{
    /**
     * Delete all permissions for a specific role.
     */
    public function purgeRolePermissions(Role $role): void
    {
        $query = $this
            ->createQueryBuilder('p')
            ->delete(\Mautic\UserBundle\Entity\Permission::class, 'p')
            ->where('p.role = :role')
            ->setParameter('role', $role)
            ->getQuery();
        $query->execute();
    }

    /**
     * Retrieves array of permissions for a set role.  If $forForm, then the array will contain.
     *
     * @param bool $forForm
     */
    public function getPermissionsByRole(Role $role, $forForm = false): array
    {
        $results = $this
            ->createQueryBuilder('p')
            ->where('p.role = :role')
            ->orderBy('p.bundle')
            ->setParameter('role', $role)
            ->getQuery()
            ->useResultCache(false)
            ->getResult(Query::HYDRATE_ARRAY);

        // rearrange the array to meet needs
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
