<?php

declare(strict_types=1);

namespace Mautic\CacheBundle\Tests\Cache;

use Mautic\CacheBundle\Cache\Adapter\FilesystemTagAwareAdapter;
use Mautic\CacheBundle\Cache\CacheProvider;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class CacheProviderTest extends TestCase
{
    private CacheProvider $cacheProvider;

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
            ->willReturn($this->createStub(FilesystemTagAwareAdapter::class));

        $this->assertEquals($this->cacheProvider->getCacheAdapter(), $this->createStub(FilesystemTagAwareAdapter::class));
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
            ->willReturn($this->createStub(FilesystemTagAwareAdapter::class));

        $this->cacheProvider->getSimpleCache();
    }
}
