<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Helper;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\EmailBundle\Helper\MarketingEmailLock;
use Symfony\Component\Filesystem\Filesystem;

final class MarketingEmailLockTest extends \PHPUnit\Framework\TestCase
{
    private string $runDirectory;

    protected function setUp(): void
    {
        $this->runDirectory = sys_get_temp_dir().'/mautic-marketing-email-lock-'.bin2hex(random_bytes(6));
        mkdir($this->runDirectory, 0775, true);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->runDirectory);
    }

    public function testDifferentContactsCanRunInParallelButTheSameContactCannot(): void
    {
        $first = $this->createLockService();
        $second = $this->createLockService();

        $firstContact = $first->acquire([10], 1);
        $secondContact = $second->acquire([10], 2);
        $sameContact  = $second->acquire([10], 1);

        $this->assertNotNull($firstContact);
        $this->assertNotNull($secondContact);
        $this->assertNull($sameContact);

        $first->release($firstContact);
        $second->release($secondContact);
    }

    public function testMultipleEmailLocksAreAcquiredInStableOrderAndRolledBack(): void
    {
        $first  = $this->createLockService();
        $second = $this->createLockService();

        $firstLocks = $first->acquire([20, 10], 7);
        $this->assertNotNull($firstLocks);
        $this->assertNull($second->acquire([10, 20], 7));

        $first->release($firstLocks);
        $secondLocks = $second->acquire([10, 20], 7);
        $this->assertIsArray($secondLocks);
        $second->release($secondLocks);
    }

    private function createLockService(): MarketingEmailLock
    {
        $paths = $this->createStub(PathsHelper::class);
        $paths->method('getSystemPath')->willReturn($this->runDirectory.'/cache');

        $parameters = $this->createStub(CoreParametersHelper::class);
        $parameters->method('get')->willReturnMap([
            ['cache_adapter', 'mautic.cache.adapter.filesystem'],
        ]);

        return new MarketingEmailLock($paths, $parameters);
    }
}
