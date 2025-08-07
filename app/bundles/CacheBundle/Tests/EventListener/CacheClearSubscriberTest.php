<?php

declare(strict_types=1);

namespace Mautic\CacheBundle\Tests\EventListener;

use Mautic\CacheBundle\Cache\AbstractCacheProvider;
use Mautic\CacheBundle\EventListener\CacheClearSubscriber;
use Monolog\Logger;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Cache\Adapter\AdapterInterface;

class CacheClearSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject&AbstractCacheProvider
     */
    private MockObject $adapter;

    public function setUp(): void
    {
        parent::setUp();
        $this->adapter = $this->getMockBuilder(AbstractCacheProvider::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['clear', 'commit', 'getCacheAdapter'])
            ->getMock();
        $this->adapter->method('clear')->willReturn(true);
        $this->adapter->method('commit')->willReturn(true);
        $this->adapter->method('getCacheAdapter')->willReturn($this->createMock(AdapterInterface::class));
    }

    public function testClear(): void
    {
        $this->adapter->expects($this->once())->method('clear')->willReturn(true);
        $subscriber = new CacheClearSubscriber($this->adapter, new Logger('test'));
        $subscriber->clear('aaa');
    }
}
