<?php

namespace Mautic\CampaignBundle\Entity;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Connections\PrimaryReadReplicaConnection;
use Mautic\CampaignBundle\Executioner\ContactFinder\Limiter\ContactLimiter;

/**
 * Trait ReplicaConnectionTrait.
 */
trait ReplicaConnectionTrait
{
    /**
     * Get a connection, preferring a replica connection if available and prudent.
     *
     * If a query is being executed with a limiter with specific contacts
     * then this could be a real-time request being handled so we should avoid forcing a replica connection.
     */
    private function getReplicaConnection(ContactLimiter $limiter = null): Connection
    {
        /** @var Connection $connection */
        $connection = $this->getEntityManager()->getConnection();
        if ($connection instanceof PrimaryReadReplicaConnection) {
            if (
                !$limiter
                || !($limiter->getContactId() || $limiter->getContactIdList())
            ) {
                $connection->ensureConnectedToReplica();
            }
        }

        return $connection;
    }
}
