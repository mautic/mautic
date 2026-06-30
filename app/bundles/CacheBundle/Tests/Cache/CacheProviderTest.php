<?php

declare(strict_types=1);

namespace Mautic\CacheBundle\Tests\Cache;

use Mautic\CacheBundle\Cache\Adapter\FilesystemTagAwareAdapter;
use Mautic\CacheBundle\Cache\CacheProvider;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CacheProviderTest extends TestCase
{
    private CacheProvider $cacheProvider;

    /**
     * @var \PHPUnit\Framework\MockObject\Stub|FilesystemTagAwareAdapter
     */
    private \PHPUnit\Framework\MockObject\Stub $adapter;

    /**
     * @var MockObject&CoreParametersHelper
     */
    private MockObject $coreParametersHelper;

    /**
     * @var MockObject&ContainerInterface
     */
    private MockObject $container;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adapter              = $this->createStub(FilesystemTagAwareAdapter::class);
        $this->coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $this->container            = $this->createMock(ContainerInterface::class);
        $this->cacheProvider        = new CacheProvider($this->coreParametersHelper, $this->container);
    }

    public function testRequestedCacheAdaptorIsReturned(): void
    {
        $this->coreParametersHelper->expects($this->once())
            ->method('get')
            ->with('cache_adapter')
            ->willReturn('foo.bar');

        $this->container->expects($this->once())
            ->method('get')
            ->with('foo.bar')
            ->willReturn($this->adapter);

        $this->assertEquals($this->cacheProvider->getCacheAdapter(), $this->adapter);
    }

    public function testSimplePsrCacheIsReturned(): void
    {
        $this->coreParametersHelper->expects($this->once())
            ->method('get')
            ->with('cache_adapter')
            ->willReturn('foo.bar');

        $this->container->expects($this->once())
            ->method('get')
            ->with('foo.bar')
            ->willReturn($this->adapter);

        $this->cacheProvider->getSimpleCache();
    }
}
