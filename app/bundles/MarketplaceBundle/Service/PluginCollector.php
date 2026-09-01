<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\Service;

use Mautic\MarketplaceBundle\Api\Connection;
use Mautic\MarketplaceBundle\Collection\PackageCollection;
use Mautic\MarketplaceBundle\Enum\PackageType;

final class PluginCollector
{
    private int $total = 0;

    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    public function collectPackages(int $page, int $limit, string $query = '', ?string $type = null): PackageCollection
    {
        $payload = $this->connection->getPlugins($page, $limit, $query, $type ?? PackageType::PLUGIN->value);

        $this->total = (int) $payload['total'];

        return PackageCollection::fromArray($payload['results'] ?? []);
    }

    public function getTotal(): int
    {
        return $this->total;
    }
}
