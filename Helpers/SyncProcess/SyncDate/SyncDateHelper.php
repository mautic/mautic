<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticIntegrationsBundle\Helpers;


use Doctrine\DBAL\Connection;

class SyncDateHelper
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * SyncDateHelper constructor.
     *
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param string $integration
     * @param string $object
     *
     * @return bool|string
     */
    public function getLastSyncDateForObject(string $integration, string $object)
    {
        $qb = $this->connection->createQueryBuilder();

        return $qb
            ->select('max(e.last_sync_date)')
            ->from(MAUTIC_TABLE_PREFIX.'integration_entity', 'e')
            ->where(
                $qb->expr()->eq('e.integration', ':integration'),
                $qb->expr()->eq('e.integration_object', ':object')
            )
            ->setParameter('integration', $integration)
            ->setParameter('object', $object)
            ->execute()
            ->fetchColumn();
    }
}