<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\DTO;

use Mautic\MarketplaceBundle\Collection\MaintainerCollection;
use Mautic\MarketplaceBundle\Collection\VersionCollection;

final class PackageDetail
{
    public function __construct(
        public PackageBase $packageBase,
        public VersionCollection $versions,
        public MaintainerCollection $maintainers,
        public GitHubInfo $githubInfo,
        public int $monthlyDownloads,
        public int $dailyDownloads,
        public \DateTimeInterface $time,
    ) {
    }

    public static function fromArray(array $array): self
    {
        return new self(
            new PackageBase(
                $array['name'],
                "https://packagist.org/packages/{$array['name']}",
                $array['repository'],
                $array['description'],
                (int) $array['downloads']['total'],
                (int) $array['favers'],
                $array['type'] ?? null,
                $array['display_name'] ?? null
            ),
            VersionCollection::fromArray($array['versions']),
            MaintainerCollection::fromArray($array['maintainers']),
            new GitHubInfo(
                $array['github_stars'],
                $array['github_watchers'],
                $array['github_forks'],
                $array['github_open_issues']
            ),
            $array['downloads']['monthly'],
            $array['downloads']['daily'],
            new \DateTimeImmutable($array['time'])
        );
    }
}
