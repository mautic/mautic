<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Doctrine\Provider;

use Doctrine\DBAL\Connection;

final class VersionProvider implements VersionProviderInterface
{
    private ?string $version = null;

    public function __construct(
        private Connection $connection
    ) {
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
        return str_contains($this->getVersion(), 'MariaDB');
    }

    public function isMySql(): bool
    {
        return !$this->isMariaDb();
    }

    private function fetchVersionFromDb(): string
    {
        return $this->connection->executeQuery('SELECT VERSION()')->fetchOne();
    }
}
