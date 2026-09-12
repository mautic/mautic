<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Doctrine\Connection;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Mautic\CoreBundle\Doctrine\Query\QueryBuilder;

class ConnectionWrapper extends Connection
{
    /**
     * @param array<string, mixed> $dbParams
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
        $this->__construct($params, $this->driver, $this->_config);
    }

    /**
     * Ask the server for its version without selecting a database when the configured one
     * is not there yet.
     *
     * Doctrine resolves the platform before Mautic has a database - creating it is the very
     * next thing the installer and the test bootstrap do - and it does so eagerly here
     * because the connection declares mapping types. DBAL retried without the database name
     * for exactly this case until version 4 dropped it.
     */
    public function getServerVersion(): string
    {
        try {
            return parent::getServerVersion();
        } catch (Exception $originalException) {
            $params = $this->getParams();

            if (!isset($params['dbname'])) {
                throw $originalException;
            }

            unset($params['dbname']);

            try {
                return $this->driver->connect($params)->getServerVersion();
            } catch (\Throwable) {
                // the server is unreachable for another reason, or the driver cannot connect
                // without a database; report the failure that was asked about
                throw $originalException;
            }
        }
    }

    /**
     * Return Mautic's query builder rather than DBAL's.
     *
     * DBAL 4 removed the query-part API and made the builder's state private, but Mautic
     * reads queries back and rewrites them after building - resolving a table name from an
     * alias, attaching index hints, rewriting join conditions. Returning the tracking
     * builder from every connection keeps that working wherever createQueryBuilder() is
     * used, without each call site having to know.
     */
    public function createQueryBuilder(): QueryBuilder
    {
        return new QueryBuilder($this);
    }
}
