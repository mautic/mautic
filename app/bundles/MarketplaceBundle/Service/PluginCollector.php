<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\Service;

use Mautic\MarketplaceBundle\Api\Connection;
use Mautic\MarketplaceBundle\Collection\PackageCollection;

class PluginCollector
{
    private Connection $connection;

    private int $total = 0;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function collectPackages(int $page = 1, int $limit, string $query = ''): PackageCollection
    {
        $payload = $this->connection->getPlugins($page, $limit, $query);

        $this->total = (int) $payload['total'];

        return PackageCollection::fromArray($payload['results']);
    }

    public function getTotal(): int
    {
        return $this->total;
    }
}
