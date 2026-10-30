<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;
use Mautic\UserBundle\Entity\Permission;
use Mautic\UserBundle\Entity\Role;

final class Version20260501090000 extends AbstractMauticMigration
{
    protected const TABLE_NAME = 'roles';

    public function up(Schema $schema): void
    {
        $rolesTable       = $this->getPrefixedTableName();

        $rows = $this->connection->executeQuery(
            sprintf('SELECT id, readable_permissions FROM %s WHERE is_admin != 1', $rolesTable)
        )->fetchAllAssociative();

        $updatedRoles = 0;
        foreach ($rows as $row) {
            $roleId         = (int) $row['id'];
            $rawPermissions = $this->unserializePermissions($row['readable_permissions']);
            $leadPerms      = $rawPermissions['lead:leads'] ?? [];
            $notesPerms     = $rawPermissions['lead:notes'] ?? null;

            if (empty($leadPerms) || null !== $notesPerms) {
                continue;
            }

            $mappedPerms = $this->mapLeadPermissionsToNotes($leadPerms);
            if ([] === $mappedPerms) {
                continue;
            }

            $rawPermissions['lead:notes'] = $mappedPerms;
            $this->connection->update(
                $rolesTable,
                ['readable_permissions' => serialize($rawPermissions)],
                ['id'                   => $roleId]
            );

            $this->upsertNotesPermission($roleId, $this->getBitwise($mappedPerms));
            ++$updatedRoles;
        }

        $this->write(sprintf('<comment>Added note permissions for %d role(s).</comment>', $updatedRoles));
    }

    /**
     * @return array<string, mixed>
     */
    private function unserializePermissions(?string $permissions): array
    {
        if (empty($permissions)) {
            return [];
        }

        $decoded = @\Mautic\CoreBundle\Helper\Serializer::decode($permissions);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param array<int, string> $leadPerms
     *
     * @return array<int, string>
     */
    private function mapLeadPermissionsToNotes(array $leadPerms): array
    {
        $allowed = [
            'viewown',
            'viewother',
            'editown',
            'editother',
            'create',
            'deleteown',
            'deleteother',
            'full',
        ];

        return array_values(array_unique(array_intersect($leadPerms, $allowed)));
    }

    /**
     * @param array<int, string> $perms
     */
    private function getBitwise(array $perms): int
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

        $bitwise = 0;
        foreach ($perms as $perm) {
            $bitwise += $permBitwise[$perm] ?? 0;
        }

        return $bitwise;
    }

    private function upsertNotesPermission(int $roleId, int $bitwise): void
    {
        /** @var EntityManagerInterface $em */
        $em = $this->container->get('doctrine.orm.entity_manager');

        $role = $em->find(Role::class, $roleId);
        if (null === $role) {
            return;
        }

        $existing = $em->createQueryBuilder()
            ->select('p')
            ->from(Permission::class, 'p')
            ->where('p.role = :role')
            ->andWhere('p.bundle = :bundle')
            ->andWhere('p.name = :name')
            ->setParameter('role', $role)
            ->setParameter('bundle', 'lead')
            ->setParameter('name', 'notes')
            ->getQuery()
            ->getOneOrNullResult();

        if ($existing instanceof Permission) {
            $existing->setBitwise($bitwise);
        } else {
            $permission = new Permission();
            $permission->setRole($role);
            $permission->setBundle('lead');
            $permission->setName('notes');
            $permission->setBitwise($bitwise);
            $em->persist($permission);
        }

        $em->flush();
    }
}
