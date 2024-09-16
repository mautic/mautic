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
    private $adapter;

    /**
     * @var string
     */
    private $random;

    public function setUp(): void
    {
        parent::setUp();
        $this->random  = sha1((string) time());
        $this->adapter = $this->getMockBuilder(FilesystemTagAwareAdapter::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['clear', 'commit'])
            ->addMethods(['getCacheAdapter'])
            ->getMock();
        $this->adapter->method('clear')->willReturn($this->random);
        $this->adapter->method('commit')->willReturn(null);
        $this->adapter->method('getCacheAdapter')->willReturn('');
    }

    public function testClear(): void
    {
        $this->adapter->expects($this->once())->method('clear')->willReturn($this->random);
        $subscriber = new CacheClearSubscriber($this->adapter, new Logger('test'));
        $subscriber->clear('aaa');
    }
}
