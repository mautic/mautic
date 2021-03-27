<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\DTO;

use Mautic\MarketplaceBundle\Collection\MaintainerCollection;
use Mautic\MarketplaceBundle\Collection\VersionCollection;

class PackageDetail extends Package
{
    private int $githubStars    = 0;
    private int $githubWatchers = 0;
    private int $githubForks    = 0;
    private int $githubOpenIssues;
    private int $dependents;
    private int $suggesters;
    private int $monthlyDownloads;
    private int $dailyDownloads;
    private \DateTimeInterface $time;
    private MaintainerCollection $maintainers;
    private VersionCollection $versions;

    public static function fromArray(array $array)
    {
        $packageDetail = new self(
            $array['name'],
            '',
            $array['repository'],
            $array['description'],
            (int) $array['downloads']['total'],
            (int) $array['favers']
        );

        $packageDetail->setGithubStars($array['github_stars']);
        $packageDetail->setGithubWatchers($array['github_watchers']);
        $packageDetail->setGithubForks($array['github_forks']);
        $packageDetail->setGithubOpenIssues($array['github_open_issues']);
        $packageDetail->setDependents($array['dependents']);
        $packageDetail->setSuggesters($array['suggesters']);
        $packageDetail->setMonthlyDownloads($array['downloads']['monthly']);
        $packageDetail->setDailyDownloads($array['downloads']['daily']);
        $packageDetail->setTime(new \DateTimeImmutable($array['time']));
        $packageDetail->setMaintainers(MaintainerCollection::fromArray($array['maintainers']));
        $packageDetail->setVersions(VersionCollection::fromArray($array['versions']));

        return $packageDetail;
    }

    public function getGithubStars(): int
    {
        return $this->githubStars;
    }

    public function setGithubStars(int $githubStars): void
    {
        $this->githubStars = $githubStars;
    }

    public function getGithubWatchers(): int
    {
        return $this->githubWatchers;
    }

    public function setGithubWatchers(int $githubWatchers): void
    {
        $this->githubWatchers = $githubWatchers;
    }

    public function getGithubForks(): int
    {
        return $this->githubForks;
    }

    public function setGithubForks(int $githubForks): void
    {
        $this->githubForks = $githubForks;
    }

    public function getGithubOpenIssues(): int
    {
        return $this->githubOpenIssues;
    }

    public function setGithubOpenIssues(int $githubOpenIssues): void
    {
        $this->githubOpenIssues = $githubOpenIssues;
    }

    public function getDependents(): int
    {
        return $this->dependents;
    }

    public function setDependents(int $dependents): void
    {
        $this->dependents = $dependents;
    }

    public function getSuggesters(): int
    {
        return $this->suggesters;
    }

    public function setSuggesters(int $suggesters): void
    {
        $this->suggesters = $suggesters;
    }

    public function getMonthlyDownloads(): int
    {
        return $this->monthlyDownloads;
    }

    public function setMonthlyDownloads(int $monthlyDownloads): void
    {
        $this->monthlyDownloads = $monthlyDownloads;
    }

    public function getDailyDownloads(): int
    {
        return $this->dailyDownloads;
    }

    public function setDailyDownloads(int $dailyDownloads): void
    {
        $this->dailyDownloads = $dailyDownloads;
    }

    public function getTime(): \DateTimeInterface
    {
        return $this->time;
    }

    public function setTime(\DateTimeInterface $time): void
    {
        $this->time = $time;
    }

    public function getMaintainers(): MaintainerCollection
    {
        return $this->maintainers;
    }

    public function setMaintainers(MaintainerCollection $maintainers): void
    {
        $this->maintainers = $maintainers;
    }

    public function getVersions(): VersionCollection
    {
        return $this->versions;
    }

    public function setVersions(VersionCollection $versions): void
    {
        $this->versions = $versions;
    }
}
