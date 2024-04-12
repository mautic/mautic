<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\DTO;

final class GitHubInfo
{
    public function __construct(
        public int $stars,
        public int $watchers,
        public int $forks,
        public int $openIssues
    ) {
    }
}
