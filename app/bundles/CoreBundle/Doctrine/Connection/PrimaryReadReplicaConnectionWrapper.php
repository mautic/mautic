<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Doctrine\Connection;

use Doctrine\DBAL\Connections\PrimaryReadReplicaConnection;
use Doctrine\DBAL\Exception;

class PrimaryReadReplicaConnectionWrapper extends PrimaryReadReplicaConnection
{
    /**
     * @param array<string, string> $dbParams
     *
     * @throws Exception
     */
    public function initConnection(array $dbParams): void
    {
        $params = $this->getParams();

        if ($this->isConnected()) {
            $this->close();
        }

        $params['host']     = $dbParams['host'];
        $params['port']     = (int) $dbParams['port'];
        $params['dbname']   = $dbParams['name'];
        $params['user']     = $dbParams['user'];
        $params['password'] = $dbParams['password'];
        $this->__construct($params, $this->_driver, $this->_config, $this->_eventManager);
    }
}
