<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Shortener;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Shortener\Shortener;
use Mautic\CoreBundle\Shortener\ShortenerServiceInterface;
use PHPUnit\Framework\TestCase;

class ShortenerTest extends TestCase
{
    /**
     * @var CoreParametersHelper|(CoreParametersHelper&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
     */
    private CoreParametersHelper|\PHPUnit\Framework\MockObject\MockObject $coreParametersHelper;

    private Shortener $shortener;

    protected function setUp(): void
    {
        $this->coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $this->shortener            = new Shortener($this->coreParametersHelper);
    }

    public function testAddService(): void
    {
        $serviceId = 'test_service';
        $service   = $this->createMock(ShortenerServiceInterface::class);

        $this->shortener->addService($serviceId, $service);

        $services = $this->shortener->getServices();
        $this->assertArrayHasKey($serviceId, $services);
        $this->assertSame($service, $services[$serviceId]);
    }

    public function testGetServiceWithInvalidName(): void
    {
        $this->coreParametersHelper
            ->method('get')
            ->willReturn('non_existent_service');

        $this->expectException(\InvalidArgumentException::class);
        $this->shortener->getService();
    }

    public function testGetServiceWithValidName(): void
    {
        $serviceId = 'test_service';
        $service   = $this->createMock(ShortenerServiceInterface::class);
        $this->shortener->addService($serviceId, $service);

        $this->coreParametersHelper
            ->method('get')
            ->willReturn($serviceId);

        $result = $this->shortener->getService();
        $this->assertSame($service, $result);
    }

    public function testGetEnabledServices(): void
    {
        $enabledService = $this->createMock(ShortenerServiceInterface::class);
        $enabledService->method('isEnabled')->willReturn(true);

        $disabledService = $this->createMock(ShortenerServiceInterface::class);
        $disabledService->method('isEnabled')->willReturn(false);

        $this->shortener->addService('enabled_service', $enabledService);
        $this->shortener->addService('disabled_service', $disabledService);

        $result = $this->shortener->getEnabledServices();
        $this->assertCount(1, $result);
        $this->assertContains($enabledService, $result);
    }

    public function testShortenUrlWithInvalidService(): void
    {
        $url = 'https://example.com';

        $this->coreParametersHelper
            ->method('get')
            ->willReturn('non_existent_service');

        $result = $this->shortener->shortenUrl($url);
        $this->assertSame($url, $result);
    }

    public function testShortenUrlWithValidService(): void
    {
        $url      = 'https://example.com';
        $shortUrl = 'https://short.url';

        $service = $this->createMock(ShortenerServiceInterface::class);
        $service->method('shortenUrl')->willReturn($shortUrl);

        $this->coreParametersHelper
            ->method('get')
            ->willReturn('test_service');

        $this->shortener->addService('test_service', $service);

        $result = $this->shortener->shortenUrl($url);
        $this->assertSame($shortUrl, $result);
    }
}
