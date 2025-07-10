<?php

declare(strict_types=1);

namespace Tests\Mautic\CoreBundle\Shortener;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Shortener\Shortener;
use Mautic\CoreBundle\Shortener\ShortenerServiceInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ShortenerTest extends TestCase
{
    /**
     * @var CoreParametersHelper|MockObject
     */
    private MockObject $coreParametersHelper;

    private Shortener $shortener;

    protected function setUp(): void
    {
        $this->coreParametersHelper = $this->getMockBuilder(CoreParametersHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->shortener = new Shortener($this->coreParametersHelper);
    }

    public function testAddService(): void
    {
        /** @var ShortenerServiceInterface|MockObject $service */
        $service = $this->createMock(ShortenerServiceInterface::class);

        $this->shortener->addService($service);

        $this->assertSame([$service::class => $service], $this->shortener->getServices());
    }

    public function testGetService(): void
    {
        /** @var ShortenerServiceInterface|MockObject $service */
        $service = $this->createMock(ShortenerServiceInterface::class);

        $this->coreParametersHelper
            ->expects($this->once())
            ->method('get')
            ->willReturn($service::class);

        $this->shortener->addService($service);

        $this->assertSame($service, $this->shortener->getService());
    }

    public function testGetServiceThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->shortener->getService();
    }

    public function testShortenUrl(): void
    {
        $url      = 'http://example.com';
        $shortUrl = 'http://exmpl.com';

        /** @var ShortenerServiceInterface|MockObject $service */
        $service = $this->createMock(ShortenerServiceInterface::class);
        $service
            ->expects($this->once())
            ->method('shortenUrl')
            ->with($url)
            ->willReturn($shortUrl);

        $this->coreParametersHelper
            ->expects($this->once())
            ->method('get')
            ->willReturn($service::class);

        $this->shortener->addService($service);

        $this->assertSame($shortUrl, $this->shortener->shortenUrl($url));
    }

    public function testGetEnabledServices(): void
    {
        $enabledService = new class implements ShortenerServiceInterface {
            public function shortenUrl(string $url): string
            {
                return 'shortUrl';
            }

            public function isEnabled(): bool
            {
                return true;
            }

            public function getPublicName(): string
            {
                return 'enabledService';
            }
        };

        $disabledService = new class implements ShortenerServiceInterface {
            public function shortenUrl(string $url): string
            {
                return 'shortUrl';
            }

            public function isEnabled(): bool
            {
                return false;
            }

            public function getPublicName(): string
            {
                return 'disabledService';
            }
        };

        $this->shortener->addService($enabledService);
        $this->shortener->addService($disabledService);

        $this->assertSame([$enabledService::class => $enabledService], $this->shortener->getEnabledServices());
    }
}
