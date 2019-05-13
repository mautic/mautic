<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc. Jan Kozak <galvani78@gmail.com>
 *
 * @link        http://mautic.com
 * @created     10.10.18
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CacheBundle\Tests;

use Mautic\CacheBundle\Cache\Adapter\FilesystemTagAwareAdapter;
use Mautic\CacheBundle\Cache\CacheProvider;
use Symfony\Component\Cache\Simple\Psr6Cache;

class CacheProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CacheProvider
     */
    private $cacheProvider;

    private $adapter;

    public function setUp()
    {
        parent::setUp();
        $this->adapter       = $this->createMock(FilesystemTagAwareAdapter::class);
        $this->cacheProvider = new CacheProvider();
    }

    public function testProviderAndSimpleCache()
    {
        $this->cacheProvider->setCacheAdapter($this->adapter);
        $this->assertEquals($this->cacheProvider->getCacheAdapter(), $this->adapter);
        $simleCache = $this->cacheProvider->getSimpleCache();
        $this->assertInstanceOf(Psr6Cache::class, $simleCache);
    }
}
