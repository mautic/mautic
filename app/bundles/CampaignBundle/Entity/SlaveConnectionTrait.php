<?php

namespace Mautic\CampaignBundle\Entity;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Connections\MasterSlaveConnection;
use Mautic\CampaignBundle\Executioner\ContactFinder\Limiter\ContactLimiter;

/**
 * Trait SlaveConnectionTrait.
 */
trait SlaveConnectionTrait
{
    /**
     * Get a connection, preferring a slave connection if available and prudent.
     *
     * If a query is being executed with a limiter with specific contacts
     * then this could be a real-time request being handled so we should avoid forcing a slave connection.
     *
     * @return Connection
     */
    private function getSlaveConnection(ContactLimiter $limiter = null)
    {
        /** @var Connection $connection */
        $connection = $this->getEntityManager()->getConnection();
        if ($connection instanceof MasterSlaveConnection) {
            if (
                !$limiter
                || !($limiter->getContactId() || $limiter->getContactIdList())
            ) {
                $connection->connect('slave');
            }
        }

        return $connection;
    }
}
