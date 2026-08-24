<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;
use Mautic\CoreBundle\Helper\Serializer;

final class Version20260824093000 extends AbstractMauticMigration
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
            $campaignPerms  = $rawPermissions['campaign:campaigns'] ?? [];
            $mappedPerms    = $this->mapCampaignPermissionsToCampaignLeadPermissions($campaignPerms);

            if ([] === $mappedPerms) {
                continue;
            }

            $existingPerms = $rawPermissions['campaign:leads'] ?? [];
            $newPerms      = $this->mergeCampaignLeadPermissions($existingPerms, $mappedPerms);

            if ($existingPerms === $newPerms) {
                continue;
            }

            $rawPermissions['campaign:leads'] = $newPerms;
            $this->connection->update(
                $rolesTable,
                ['readable_permissions' => serialize($rawPermissions)],
                ['id'                   => $roleId]
            );

            $this->upsertCampaignLeadPermission($permissionsTable, $roleId, $this->getBitwise($newPerms));
            ++$updatedRoles;
        }

        $this->write(sprintf('<comment>Added campaign contact permissions for %d role(s).</comment>', $updatedRoles));
    }

    /**
     * @return array<string, mixed>
     */
    private function unserializePermissions(?string $permissions): array
    {
        if (empty($permissions)) {
            return [];
        }

        $decoded = @Serializer::decode($permissions);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param array<int, string> $campaignPerms
     *
     * @return array<int, string>
     */
    private function mapCampaignPermissionsToCampaignLeadPermissions(array $campaignPerms): array
    {
        if (in_array('full', $campaignPerms, true)) {
            return ['full'];
        }

        $mappedPerms = [];
        if (in_array('editown', $campaignPerms, true)) {
            $mappedPerms[] = 'addown';
        }

        if (in_array('editother', $campaignPerms, true)) {
            $mappedPerms[] = 'addown';
            $mappedPerms[] = 'addother';
        }

        return array_values(array_unique($mappedPerms));
    }

    /**
     * @param array<int, string> $existingPerms
     * @param array<int, string> $mappedPerms
     *
     * @return array<int, string>
     */
    private function mergeCampaignLeadPermissions(array $existingPerms, array $mappedPerms): array
    {
        if (in_array('full', $existingPerms, true) || in_array('full', $mappedPerms, true)) {
            return ['full'];
        }

        $newPerms = array_values(array_unique(array_merge($existingPerms, $mappedPerms)));
        if (in_array('addother', $newPerms, true) && !in_array('addown', $newPerms, true)) {
            $newPerms[] = 'addown';
        }

        return $newPerms;
    }

    /**
     * @param array<int, string> $perms
     */
    private function getBitwise(array $perms): int
    {
        $permBitwise = [
            'addown'   => 2,
            'addother' => 4,
            'full'     => 1024,
        ];

        $bitwise = 0;
        foreach ($perms as $perm) {
            $bitwise += $permBitwise[$perm] ?? 0;
        }

        return $bitwise;
    }

    private function upsertCampaignLeadPermission(string $permissionsTable, int $roleId, int $bitwise): void
    {
        $exists = $this->connection->fetchOne(
            sprintf('SELECT id FROM %s WHERE role_id = :roleId AND bundle = :bundle AND name = :name', $permissionsTable),
            [
                'roleId' => $roleId,
                'bundle' => 'campaign',
                'name'   => 'leads',
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
                'bundle'  => 'campaign',
                'name'    => 'leads',
                'bitwise' => $bitwise,
            ]
        );
    }
}
