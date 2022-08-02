<?php

declare(strict_types=1);

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
    private Connection $connection;

    /**
     * @var string
     */
    private $version;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function getVersion(): string
    {
        if (null === $this->version) {
            $this->version = $this->fetchVersionFromDb();
        }

        return $this->version;
    }

    public function isMariaDb(): bool
    {
        return false !== strpos($this->getVersion(), 'MariaDB');
    }

    public function isMySql(): bool
    {
        return !$this->isMariaDb();
    }

    private function fetchVersionFromDb(): string
    {
        return $this->connection->executeQuery('SELECT VERSION()')->fetchColumn();
    }
}
