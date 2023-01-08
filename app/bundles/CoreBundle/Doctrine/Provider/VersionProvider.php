<?php

declare(strict_types=1);

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
