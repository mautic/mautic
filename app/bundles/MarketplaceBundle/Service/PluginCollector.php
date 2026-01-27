<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\Service;

use Mautic\CoreBundle\Release\ThisRelease;
use Mautic\MarketplaceBundle\Api\Connection;
use Mautic\MarketplaceBundle\Collection\PackageCollection;
use Mautic\MarketplaceBundle\DTO\AllowlistEntry;

class PluginCollector
{
    /**
     * @var AllowlistEntry[]
     */
    private array $allowlistedPackages = [];

    private int $total = 0;

    public function __construct(
        private Connection $connection,
        private Allowlist $allowlist,
    ) {
    }

    public function collectPackages(int $page, int $limit, string $query = '', ?string $type = null): PackageCollection
    {
        $allowlist = $this->allowlist->getAllowList();

        // Remove type commands from query for text search
        $searchQuery = $this->removeTypeCommandsFromQuery($query);

        if (!empty($allowlist)) {
            $this->allowlistedPackages = $this->filterAllowlistedPackagesForCurrentMauticVersion($allowlist->entries);

            if (null !== $type) {
                $this->allowlistedPackages = $this->filterAllowlistedPackagesByType($this->allowlistedPackages, $type);
            }

            if (!empty($searchQuery)) {
                $this->allowlistedPackages = $this->filterAllowlistedPackagesByName($this->allowlistedPackages, $searchQuery);
            }

            $payload = $this->getAllowlistedPackages($page, $limit);
        } else {
            $payload = $this->connection->getPlugins($page, $limit, $searchQuery, $type ?? AllowlistEntry::TYPE_PLUGIN);
        }

        $this->total = (int) $payload['total'];

        return PackageCollection::fromArray($payload['results']);
    }

    private function removeTypeCommandsFromQuery(string $query): string
    {
        return trim(preg_replace('/is:(plugin|theme|campaign)/i', '', $query));
    }

    /**
     * @param AllowlistEntry[] $entries
     *
     * @return AllowlistEntry[]
     */
    private function filterAllowlistedPackagesByName(array $entries, string $query): array
    {
        $query = strtolower($query);

        return array_values(array_filter($entries, function (AllowlistEntry $entry) use ($query): bool {
            return str_contains(strtolower($entry->package), $query)
                || str_contains(strtolower($entry->displayName), $query);
        }));
    }

    /**
     * @param AllowlistEntry[] $entries
     *
     * @return AllowlistEntry[]
     */
    private function filterAllowlistedPackagesByType(array $entries, string $type): array
    {
        return array_values(array_filter($entries, fn (AllowlistEntry $entry): bool => $entry->type === $type));
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    /**
     * @param AllowlistEntry[] $entries
     *
     * @return AllowlistEntry[]
     */
    private function filterAllowlistedPackagesForCurrentMauticVersion(array $entries): array
    {
        $mauticVersion = ThisRelease::getMetadata()->getVersion();

        return array_filter($entries, function (AllowlistEntry $entry) use ($mauticVersion): bool {
            if (
                !empty($entry->minimumMauticVersion)
                && !version_compare($mauticVersion, $entry->minimumMauticVersion, '>=')
            ) {
                return false;
            }

            if (
                !empty($entry->maximumMauticVersion)
                && !version_compare($mauticVersion, $entry->maximumMauticVersion, '<=')
            ) {
                return false;
            }

            return true;
        });
    }

    /**
     * During the Marketplace beta period, we only want to show packages that are explicitly
     * allowlisted. This function only gets allowlisted packages from Packagist. Their API doesn't
     * support querying multiple packages at once, so we simply do a foreach loop.
     *
     * @return array<string,mixed>
     */
    private function getAllowlistedPackages(int $page, int $limit): array
    {
        $total   = count($this->allowlistedPackages);
        $results = [];

        if (0 === $total) {
            return [
                'total'   => 0,
                'results' => [],
            ];
        }

        /** @var array<int, AllowlistEntry[]> $chunks */
        $chunks = array_chunk($this->allowlistedPackages, $limit);
        // Array keys start at 0 but page numbers start at 1
        $pageChunk = $page - 1;

        foreach ($chunks[$pageChunk] as $entry) {
            if (count($results) >= $limit) {
                continue;
            }

            $payload = $this->connection->getPlugins(1, 1, $entry->package);

            if (isset($payload['results'][0])) {
                $results[] = $payload['results'][0] + $entry->toArray();
            }
        }

        return [
            'total'   => $total,
            'results' => $results,
        ];
    }
}
