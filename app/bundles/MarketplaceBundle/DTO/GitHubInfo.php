<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\DTO;

final class GitHubInfo
{
    public int $stars;
    public int $watchers;
    public int $forks;
    public int $openIssues;

    public function __construct(int $stars, int $watchers, int $forks, int $openIssues)
    {
        $this->stars      = $stars;
        $this->watchers   = $watchers;
        $this->forks      = $forks;
        $this->openIssues = $openIssues;
    }
}
