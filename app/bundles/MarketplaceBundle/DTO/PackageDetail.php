<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\DTO;

use Mautic\MarketplaceBundle\Collection\MaintainerCollection;
use Mautic\MarketplaceBundle\Collection\VersionCollection;

final class PackageDetail
{
    private PackageBase $packageBase;
    private GitHubInfo $githubInfo;
    private int $monthlyDownloads;
    private int $dailyDownloads;
    private \DateTimeInterface $time;
    private MaintainerCollection $maintainers;
    private VersionCollection $versions;

    public function __construct(
        PackageBase $packageBase,
        VersionCollection $versions,
        MaintainerCollection $maintainers,
        GitHubInfo $githubInfo,
        int $monthlyDownloads,
        int $dailyDownloads,
        \DateTimeInterface $time
    ) {
        $this->packageBase = $packageBase;
        $this->versions = $versions;
        $this->maintainers = $maintainers;
        $this->githubInfo = $githubInfo;
        $this->monthlyDownloads = $monthlyDownloads;
        $this->dailyDownloads = $dailyDownloads;
        $this->time = $time;
    }

    public static function fromArray(array $array)
    {
        return new self(
            new PackageBase(
                $array['name'],
                '',
                $array['repository'],
                $array['description'],
                (int) $array['downloads']['total'],
                (int) $array['favers']
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

    public function getPackageBase(): PackageBase
    {
        return $this->packageBase;
    }

    public function getGithubInfo(): GitHubInfo
    {
        return $this->githubInfo;
    }

    public function getMonthlyDownloads(): int
    {
        return $this->monthlyDownloads;
    }

    public function getDailyDownloads(): int
    {
        return $this->dailyDownloads;
    }

    public function getTime(): \DateTimeInterface
    {
        return $this->time;
    }

    public function getMaintainers(): MaintainerCollection
    {
        return $this->maintainers;
    }

    public function getVersions(): VersionCollection
    {
        return $this->versions;
    }
}
