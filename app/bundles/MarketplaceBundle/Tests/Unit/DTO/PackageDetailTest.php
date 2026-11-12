<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\Tests\Unit\DTO;

use Mautic\MarketplaceBundle\DTO\PackageDetail;
use PHPUnit\Framework\TestCase;

final class PackageDetailTest extends TestCase
{
    public function testPackageSharedFromMauticHasNoGitHubInfoNorMaintainerAvatar(): void
    {
        // As the marketplace stores a campaign shared from Mautic
        $package = PackageDetail::fromArray([
            'name'               => 'vendor/shared-campaign',
            'description'        => 'A shared campaign',
            'type'               => 'mautic-resource',
            'repository'         => null,
            'downloads'          => ['total' => 3],
            'favers'             => null,
            'github_stars'       => null,
            'github_watchers'    => null,
            'github_forks'       => null,
            'github_open_issues' => null,
            'maintainers'        => [['name' => 'Publisher']],
            'versions'           => [],
            'time'               => '2026-10-24T16:08:10+00:00',
        ]);

        $this->assertSame(3, $package->packageBase->downloads);
        $this->assertSame(0, $package->packageBase->favers);
        $this->assertNull($package->packageBase->repository);
        $this->assertSame(0, $package->githubInfo->stars);
        $this->assertSame(0, $package->githubInfo->openIssues);
        $this->assertSame(0, $package->monthlyDownloads);
        $this->assertSame(0, $package->dailyDownloads);
        $this->assertSame('Publisher', $package->maintainers->current()->name);
        $this->assertNull($package->maintainers->current()->avatar);
    }

    public function testPackageFromPackagistKeepsItsInfo(): void
    {
        $package = PackageDetail::fromArray([
            'name'               => 'vendor/plugin',
            'description'        => 'A plugin',
            'type'               => 'mautic-plugin',
            'repository'         => 'https://github.com/vendor/plugin',
            'downloads'          => ['total' => 30, 'monthly' => 20, 'daily' => 1],
            'favers'             => 4,
            'github_stars'       => 5,
            'github_watchers'    => 6,
            'github_forks'       => 7,
            'github_open_issues' => 8,
            'maintainers'        => [['name' => 'Maintainer', 'avatar_url' => 'https://example.com/avatar.png']],
            'versions'           => [],
            'time'               => '2026-10-24T16:08:10+00:00',
        ]);

        $this->assertSame('https://github.com/vendor/plugin', $package->packageBase->repository);
        $this->assertSame(4, $package->packageBase->favers);
        $this->assertSame([5, 6, 7, 8], [$package->githubInfo->stars, $package->githubInfo->watchers, $package->githubInfo->forks, $package->githubInfo->openIssues]);
        $this->assertSame([20, 1], [$package->monthlyDownloads, $package->dailyDownloads]);
        $this->assertSame('https://example.com/avatar.png', $package->maintainers->current()->avatar);
    }
}
