<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc. Jan Kozak <galvani78@gmail.com>
 *
 * @link        http://mautic.com
 * @created     10.10.18
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CacheBundle\Tests\EventListener;

use Mautic\CacheBundle\Cache\Adapter\FilesystemTagAwareAdapter;
use Mautic\CacheBundle\EventListener\CacheClearSubscriber;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

class CacheClearSubscriberTest extends TestCase
{
    /**
     * @var
     */
    private $adapter;

    /**
     * @var string
     */
    private $random;

    public function setUp()
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

    public function testClear()
    {
        $this->adapter->expects($this->once())->method('clear')->willReturn($this->random);
        $subscriber = new CacheClearSubscriber($this->adapter, new Logger('test'));
        $subscriber->clear('aaa');
    }
}
