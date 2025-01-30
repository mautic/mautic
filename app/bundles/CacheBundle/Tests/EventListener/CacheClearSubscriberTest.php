<?php

declare(strict_types=1);

namespace Mautic\CacheBundle\Tests\EventListener;

use Mautic\CacheBundle\Cache\Adapter\FilesystemTagAwareAdapter;
use Mautic\CacheBundle\EventListener\CacheClearSubscriber;
use Monolog\Logger;
use PHPUnit\Framework\MockObject\MockObject;

class CacheClearSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject|FilesystemTagAwareAdapter
     */
    private MockObject $adapter;

    public function setUp(): void
    {
        parent::setUp();
        $this->adapter = $this->getMockBuilder(FilesystemTagAwareAdapter::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['clear', 'commit'])
            ->addMethods(['getCacheAdapter']) // because CacheProvider does not have an interface.
            ->getMock();
        $this->adapter->method('clear')->willReturn(true);
        $this->adapter->method('commit')->willReturn(true);
        $this->adapter->method('getCacheAdapter')->willReturn('');
    }

    public function testClear(): void
    {
        $this->adapter->expects($this->once())->method('clear')->willReturn(true);
        $subscriber = new CacheClearSubscriber($this->adapter, new Logger('test'));
        $subscriber->clear('aaa');
    }
}
