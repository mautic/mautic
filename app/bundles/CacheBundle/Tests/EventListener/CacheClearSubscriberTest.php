<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

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
            ->setMethods(['clear', 'getCacheAdapter', 'commit'])
            ->getMock();
        $this->adapter->method('clear')->willReturn($this->random);
        $this->adapter->method('commit')->willReturn(null);
    }

    public function testClear(): void
    {
        $this->adapter->expects($this->once())->method('clear')->willReturn($this->random);
        $subscriber = new CacheClearSubscriber($this->adapter, new Logger('test'));
        $subscriber->clear('aaa');
    }
}
