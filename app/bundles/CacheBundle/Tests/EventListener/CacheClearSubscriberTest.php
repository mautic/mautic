<?php

declare(strict_types=1);

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

class CacheClearSubscriberTest extends \PHPUnit_Framework_TestCase
{
    private $adapter;

    /**
     * @var string
     */
    private $random;

    public function setUp()
    {
        parent::setUp();
        $this->random  = sha1((string) time());
        $this->adapter = $this->getMockBuilder(FilesystemTagAwareAdapter::class)->enableProxyingToOriginalMethods()
                              ->setMethods(['clear', 'getCacheAdapter'])
                              ->getMock();
    }

    public function testClear()
    {
        $this->adapter->expects($this->once())->method('clear')->willReturn($this->random);
        $subscriber = new CacheClearSubscriber($this->adapter);
        $subscriber->clear('aaa');
    }
}
