<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\DTO;

use Mautic\MarketplaceBundle\Enum\PackageType;

final class AllowlistEntry
{
    public function __construct(
        /**
         * Packagist package in the format vendor/package.
         */
        public string $package,
        /**
         * Human readable name.
         */
        public string $displayName,
        /**
         * Minimum Mautic version in semver format (e.g. 4.1.2).
         */
        public ?string $minimumMauticVersion,
        /**
         * Maximum Mautic version in semver format (e.g. 4.1.2).
         */
        public ?string $maximumMauticVersion,
        /**
         * Package type: mautic-plugin, mautic-theme, or mautic-campaign.
         */
        public string $type = PackageType::PLUGIN->value,
    ) {
    }

    /**
     * @param array<string,mixed> $array
     */
    public static function fromArray(array $array): AllowlistEntry
    {
        return new self(
            $array['package'],
            $array['display_name'] ?? '',
            $array['minimum_mautic_version'],
            $array['maximum_mautic_version'],
            $array['type'] ?? PackageType::PLUGIN->value
        );
    }

    /**
     * @return array<string,string>
     */
    public function toArray(): array
    {
        return [
            'package'                => $this->package,
            'display_name'           => $this->displayName,
            'minimum_mautic_version' => $this->minimumMauticVersion,
            'maximum_mautic_version' => $this->maximumMauticVersion,
            'type'                   => $this->type,
        ];
    }
}
