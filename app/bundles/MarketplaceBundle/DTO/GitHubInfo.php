<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\DTO;

final class GitHubInfo
{
    private int $stars;
    private int $watchers;
    private int $forks;
    private int $openIssues;

    public function __construct(int $stars, int $watchers, int $forks, int $openIssues)
    {
        $this->stars = $stars;
        $this->watchers = $watchers;
        $this->forks = $forks;
        $this->openIssues = $openIssues;
    }

    public function getStars(): int
    {
        return $this->stars;
    }

    public function getWatchers(): int
    {
        return $this->watchers;
    }

    public function getForks(): int
    {
        return $this->forks;
    }

    public function getOpenIssues(): int
    {
        return $this->openIssues;
    }
}
