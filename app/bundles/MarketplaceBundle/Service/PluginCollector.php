<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\Service;

use Mautic\MarketplaceBundle\Api\Connection;
use Mautic\MarketplaceBundle\Collection\PackageCollection;

class PluginCollector
{
    private int $total = 0;

    public function __construct(
        private Connection $connection
    ) {
    }

    public function collectPackages(int $page, int $limit, string $query = ''): PackageCollection
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
