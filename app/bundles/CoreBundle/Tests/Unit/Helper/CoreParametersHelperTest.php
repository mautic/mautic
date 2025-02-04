<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CoreParametersHelperTest extends TestCase
{
    /**
     * @var MockObject|ContainerInterface
     */
    private MockObject $container;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
    }

    public function testAllReturnsResolvedParameters(): void
    {
        $this->container->method('hasParameter')
            ->willReturnCallback(
                fn (string $key) => 'mautic.cache_path' === $key
            );

        $this->container->expects($this->once())
            ->method('getParameter')
            ->with('mautic.cache_path')
            ->willReturn('/path/to/cache');

        $all = $this->getHelper()->all();

        // Assert that a few of the config keys exist
        Assert::assertArrayHasKey('api_enabled', $all);
        Assert::assertArrayHasKey('cache_path', $all);
        Assert::assertSame('/path/to/cache', $all['cache_path']);
        Assert::assertArrayHasKey('log_path', $all);
    }

    private function getHelper(): CoreParametersHelper
    {
        return new CoreParametersHelper($this->container);
    }
}
