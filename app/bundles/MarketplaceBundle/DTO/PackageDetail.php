<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\DTO;

use Mautic\MarketplaceBundle\Collection\MaintainerCollection;
use Mautic\MarketplaceBundle\Collection\ReviewCollection;
use Mautic\MarketplaceBundle\Collection\VersionCollection;

final class PackageDetail
{
    public function __construct(
        public PackageBase $packageBase,
        public VersionCollection $versions,
        public MaintainerCollection $maintainers,
        public ReviewCollection $reviews,
        public GitHubInfo $githubInfo,
        public int $monthlyDownloads,
        public int $dailyDownloads,
        public \DateTimeInterface $time,
        public ?string $language = null,
        public int $dependents = 0,
        public int $suggesters = 0,
    ) {
    }

    public static function fromArray(array $array): self
    {
        $reviews = ReviewCollection::fromArray($array['reviews'] ?? []);

        return new self(
            new PackageBase(
                $array['name'],
                "https://packagist.org/packages/{$array['name']}",
                $array['repository'] ?? null,
                $array['description'] ?? null,
                (int) ($array['downloads']['total'] ?? 0),
                (int) ($array['favers'] ?? 0),
                $reviews->getAverageRating(),
                $reviews->count(),
                $array['type'] ?? null,
                $array['display_name'] ?? $array['displayname'] ?? null
            ),
            VersionCollection::fromArray($array['versions']),
            MaintainerCollection::fromArray($array['maintainers']),
            $reviews,
            // Packages shared from Mautic have no GitHub repository nor download history
            new GitHubInfo(
                (int) ($array['github_stars'] ?? 0),
                (int) ($array['github_watchers'] ?? 0),
                (int) ($array['github_forks'] ?? 0),
                (int) ($array['github_open_issues'] ?? 0)
            ),
            (int) ($array['downloads']['monthly'] ?? 0),
            (int) ($array['downloads']['daily'] ?? 0),
            new \DateTimeImmutable($array['time']),
            $array['language'] ?? null,
            (int) ($array['dependents'] ?? 0),
            (int) ($array['suggesters'] ?? 0)
        );
    }
}
