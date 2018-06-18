<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Entity;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Connections\MasterSlaveConnection;

/**
 * Trait SlaveConnectionTrait.
 */
trait SlaveConnectionTrait
{
    /**
     * Get a connection, preferring a slave connection if available and prudent.
     *
     * @return mixed
     */
    private function getSlaveConnection()
    {
        /** @var Connection $connection */
        $connection = $this->getEntityManager()->getConnection();
        if ($connection instanceof MasterSlaveConnection) {
            $connection->connect('slave');
        }

        return $connection;
    }
}
