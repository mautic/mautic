<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Sync\Helper;


use Doctrine\DBAL\Connection;
use MauticPlugin\IntegrationsBundle\Sync\Exception\ObjectNotSupportedException;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\MauticSyncDataExchange;

class OwnerHelper
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * OwnerHelper constructor.
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
    public function getObjectOwners(string $object, array $ids)
    {
        if (empty($ids)) {
            return [];
        }

        $qb      = $this->connection->createQueryBuilder();
        $results = $qb->select('o.owner_id, o.id')
            ->from(MAUTIC_TABLE_PREFIX.$this->getTable($object), 'o')
            ->where(
                $qb->expr()->in($object, array_map('intval', $ids))
            )
            ->execute()->fetchAll();

        $owners = [];
        foreach ($results as $result) {
            $ownerId = $result['owner_id'];
            if (!isset($owners[$ownerId])) {
                $owners[$ownerId] = [];
            }

            $owners[$ownerId][] = $result['id'];
        }

        return $owners;
    }

    /**
     * @param string $object
     *
     * @return string
     * @throws ObjectNotSupportedException
     */
    private function getTable(string $object): string
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