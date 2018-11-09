<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Sync\Notification\Helper;


use Doctrine\DBAL\Connection;
use MauticPlugin\IntegrationsBundle\Sync\Exception\ObjectNotSupportedException;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\MauticSyncDataExchange;

class UserHelper
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * UserHelper constructor.
     *
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param string $object
     * @param array  $ids
     *
     * @return array
     * @throws ObjectNotSupportedException
     */
    public function getOwners(string $object, array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $qb      = $this->connection->createQueryBuilder();
        $results = $qb->select('o.owner_id, o.id')
            ->from(MAUTIC_TABLE_PREFIX.$this->getObjectTable($object), 'o')
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->isNotNull('o.owner_id'),
                    $qb->expr()->in('o.id', array_map('intval', $ids))
                )
            )
            ->execute()->fetchAll();

        $owners = [];
        foreach ($results as $result) {
            $ownerId = $result['owner_id'];
            if (!isset($owners[$ownerId])) {
                $owners[$ownerId] = [];
            }

            $owners[$ownerId][] = (int) $result['id'];
        }

        return $owners;
    }


    /**
     * @param string $object
     * @param int    $id
     *
     * @return string|null
     * @throws ObjectNotSupportedException
     */
    public function getOwner(string $object, int $id): ?string
    {
        $qb      = $this->connection->createQueryBuilder();
        $result = $qb->select('o.owner_id')
            ->from(MAUTIC_TABLE_PREFIX.$this->getObjectTable($object), 'o')
            ->where(
                $qb->expr()->eq('o.id', $id)
            )
            ->execute()->fetchColumn();

        return $result ? (int) $result : null;
    }


    /**
     * @return array
     */
    public function getAdminUsers(): array
    {
        $qb      = $this->connection->createQueryBuilder();
        $results = $qb->select('u.id')
            ->from(MAUTIC_TABLE_PREFIX.'users', 'u')
            ->join('u', MAUTIC_TABLE_PREFIX.'roles', 'r', 'r.id = u.role_id')
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->eq('r.is_published', 1),
                    $qb->expr()->eq('r.is_admin', 1),
                    $qb->expr()->eq('u.is_published', 1)
                )
            )
            ->execute()->fetchAll();

        $admins = [];
        foreach ($results as $result) {
            $admins[] = (int) $result['id'];
        }

        return $admins;
    }

    /**
     * @param string $object
     *
     * @return string
     * @throws ObjectNotSupportedException
     */
    private function getObjectTable(string $object): string
    {
        switch ($object) {
            case MauticSyncDataExchange::OBJECT_CONTACT:
                return 'leads';
            case MauticSyncDataExchange::OBJECT_COMPANY:
                return 'companies';
            default:
                throw new ObjectNotSupportedException('Mautic', $object);
        }
    }
}