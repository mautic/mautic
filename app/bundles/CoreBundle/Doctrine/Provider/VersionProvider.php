<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Doctrine\Provider;

use Doctrine\DBAL\Connection;

final class VersionProvider implements VersionProviderInterface
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var string
     */
    private $version;

    /**
     * @param Connection $db
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Loads the version from the database and stores it to a property if not set yet.
     *
     * @return string
     */
    public function fetchVersion()
    {
        if (!$this->version) {
            $this->version = $this->connection->executeQuery('SELECT VERSION()')->fetchColumn();
        }

        return $this->version;
    }

    /**
     * @return bool
     */
    public function isMariaDb()
    {
        return strpos($this->fetchVersion(), 'MariaDB') !== false;
    }

    /**
     * @return bool
     */
    public function isMySql()
    {
        return !$this->isMariaDb();
    }
}
