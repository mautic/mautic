<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20260501090000 extends AbstractMauticMigration
{
    protected const TABLE_NAME = 'roles';

    public function up(Schema $schema): void
    {
        $rolesTable       = $this->getPrefixedTableName();
        $permissionsTable = $this->prefix.'permissions';

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

            $this->upsertNotesPermission($permissionsTable, $roleId, $this->getBitwise($mappedPerms));
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

    private function upsertNotesPermission(string $permissionsTable, int $roleId, int $bitwise): void
    {
        $exists = $this->connection->fetchOne(
            sprintf('SELECT id FROM %s WHERE role_id = :roleId AND bundle = :bundle AND name = :name', $permissionsTable),
            [
                'roleId' => $roleId,
                'bundle' => 'lead',
                'name'   => 'notes',
            ]
        );

        if (false !== $exists) {
            $this->connection->update(
                $permissionsTable,
                ['bitwise' => $bitwise],
                ['id'      => (int) $exists]
            );

            return;
        }

        $this->connection->insert(
            $permissionsTable,
            [
                'role_id' => $roleId,
                'bundle'  => 'lead',
                'name'    => 'notes',
                'bitwise' => $bitwise,
            ]
        );
    }
}
