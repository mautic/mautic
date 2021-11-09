<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\Service;

use Mautic\MarketplaceBundle\Api\Connection;
use Mautic\MarketplaceBundle\Collection\PackageCollection;

class PluginCollector
{
    private Connection $connection;

    private int $total = 0;
    private array $allowListedPackages = [];

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->allowListedPackages = json_decode(file_get_contents(__DIR__ . '/tempAllowList.json'), true);
    }

    public function collectPackages(int $page = 1, int $limit, string $query = ''): PackageCollection
    {
        if (count($this->allowListedPackages) > 0) {
            $payload = $this->getAllowlistedPackages($page, $limit, $query);
        } else {
            $payload = $this->connection->getPlugins($page, $limit, $query);
        }

        $this->total = (int) $payload['total'];

        return PackageCollection::fromArray($payload['results']);
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    /**
     * During the Marketplace beta period, we only want to show packages that are explicitly
     * allowlisted. This function only gets allowlisted packages from Packagist. Their API doesn't
     * support querying multiple packages at once, so we simply do a foreach loop.
     */
    private function getAllowlistedPackages(int $page = 1, int $limit, string $query = ''): array {
        $total = count($this->allowListedPackages);
        $results = [];

        $chunks = array_chunk($this->allowListedPackages, $limit);
        // Array keys start at 0 but page numbers start at 1
        $pageChunk = $page - 1; 

        foreach ($chunks[$pageChunk] as $packageName) {
            if (count($results) >= $limit) {
                continue;
            }

            $payload = $this->connection->getPlugins(1, 1, $packageName);

            if (!empty($payload['results'])) {
                $results[] = $payload['results'][0];
            }
        }

        return [
            'total' => $total,
            'results' => $results
        ];
    }
}
