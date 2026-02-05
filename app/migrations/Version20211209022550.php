<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\UserBundle\Entity\Permission;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Model\RoleModel;

final class Version20211209022550 extends AbstractMauticMigration
{
    public function postUp(Schema $schema): void
    {
        /** @var RoleModel $roleModel */
        $roleModel = $this->container->get(ModelFactory::class)->getModel('user.role');

        // Build custom query to force OBJECT hydration
        $qb = $roleModel->getRepository()->createQueryBuilder('r');

        $qb->where($qb->expr()->eq('r.isAdmin', ':isAdmin'))
            ->setParameter('isAdmin', 0)
            ->orderBy('r.id', 'ASC');

        $query = $qb->getQuery();
        $query->setHint(Query::HINT_REFRESH, true);          // Force refresh / full hydration
        $query->setHydrationMode(Query::HYDRATE_OBJECT);     // Explicitly force objects

        $roles = $query->getResult();

        if (empty($roles)) {
            $this->debugMessage('[INFO] No non-admin roles found – skipping permission migration.');

            return;
        }

        /** @var EntityManagerInterface $em */
        $em = $this->container->get('doctrine.orm.entity_manager');

        $updatedCount = 0;

        /** @var Role $role */
        foreach ($roles as $role) {
            // Now $role is always Role object – no array fallback needed
            $rawPermissions = $role->getRawPermissions();

            if (empty($rawPermissions)) {
                continue;
            }

            $leadPermissions = $rawPermissions['lead:leads'] ?? [];
            $listPermissions = $rawPermissions['lead:lists'] ?? [];

            if (empty($leadPermissions) && empty($listPermissions)) {
                continue;
            }

            // Map leads → lists
            $newPermissions = $leadPermissions;

            if (!in_array('full', $newPermissions, true)) {
                if (in_array('viewown', $leadPermissions, true)) {
                    $newPermissions[] = 'create';
                }
                $newPermissions = array_merge($newPermissions, $listPermissions);
            }

            $newPermissions = array_unique($newPermissions);

            $bitwise = $this->calculateBitwise($newPermissions);

            $this->updateRolePermissions($role, $bitwise, $newPermissions, $em);

            ++$updatedCount;
        }

        $em->flush();

        if ($updatedCount > 0) {
            $this->debugMessage("[INFO] Updated permissions for $updatedCount non-admin role(s).");
        } else {
            $this->debugMessage('[INFO] No roles required permission updates.');
        }
    }

    /**
     * @param string[] $permissions
     */
    private function calculateBitwise(array $permissions): int
    {
        $bitwiseMap = [
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
        foreach ($permissions as $perm) {
            $bit += $bitwiseMap[$perm] ?? 0;
        }

        return $bit;
    }

    private function debugMessage(string $string): void
    {
        // uncomment me for debug
        // echo $string.PHP_EOL;
    }

    private function updateRolePermissions(Role $role, int $bitwise, array $newPermissions, EntityManagerInterface $em): void
    {
        $updated = false;

        foreach ($role->getPermissions() as $permission) {
            if ('lists' === $permission->getName()) {
                $permission->setBitwise($bitwise);
                $em->persist($permission);
                $updated = true;
                break;
            }
        }

        if (!$updated) {
            $permission = new Permission();
            $permission->setBundle('lead');
            $permission->setName('lists');
            $permission->setBitwise($bitwise);
            $em->persist($permission);
            $role->addPermission($permission);
        }

        $raw               = $role->getRawPermissions();
        $raw['lead:lists'] = $newPermissions;
        $role->setRawPermissions($raw);

        $em->persist($role);
    }
}
