<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\DTO;

use Mautic\MarketplaceBundle\Exception\RecordNotFoundException;

final class Allowlist
{
    /**
     * @var AllowlistEntry[]
     */
    public array $entries;

    /**
     * @param AllowlistEntry[] $entries
     */
    public function __construct(array $entries)
    {
        $this->entries = $entries;
    }

    /**
     * @param array<string,mixed> $array
     */
    public static function fromArray(array $array): Allowlist
    {
        return new self(
            array_map(fn (array $item) => AllowlistEntry::fromArray($item), $array['allowlist'] ?? []),
        );
    }

    public function findPackageByName(string $packageName): AllowlistEntry
    {
        foreach ($this->entries as $entry) {
            if ($entry->package === $packageName) {
                return $entry;
            }
        }

        throw new RecordNotFoundException("Package '$packageName' not found in allowlist.");
    }
}
