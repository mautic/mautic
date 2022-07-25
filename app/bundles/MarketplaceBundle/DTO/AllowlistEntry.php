<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\DTO;

final class AllowlistEntry
{
    /**
     * Packagist package in the format vendor/package.
     */
    public string $package;

    /**
     * Minimum Mautic version in semver format (e.g. 4.1.2).
     */
    public ?string $minimumMauticVersion;

    /**
     * Maximum Mautic version in semver format (e.g. 4.1.2).
     */
    public ?string $maximumMauticVersion;

    public function __construct(string $package, ?string $minimumMauticVersion, ?string $maximumMauticVersion)
    {
        $this->package              = $package;
        $this->minimumMauticVersion = $minimumMauticVersion;
        $this->maximumMauticVersion = $maximumMauticVersion;
    }

    /**
     * @param array<string,mixed> $array
     */
    public static function fromArray(array $array): AllowlistEntry
    {
        return new self(
            $array['package'],
            $array['minimum_mautic_version'],
            $array['maximum_mautic_version']
        );
    }
}
