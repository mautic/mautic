<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Shortener;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Shortener\Shortener;
use Mautic\CoreBundle\Shortener\ShortenerServiceInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ShortenerTest extends TestCase
{
    /**
     * @var MockObject&CoreParametersHelper
     */
    private MockObject $coreParametersHelper;

    protected function setUp(): void
    {
        $this->coreParametersHelper = $this->createMock(CoreParametersHelper::class);
    }

    public function testAddService(): void
    {
        /** @var ShortenerServiceInterface|MockObject $service */
        $service = $this->createStub(ShortenerServiceInterface::class);

        $shortener = new Shortener($this->coreParametersHelper, [$service]);

        $this->assertSame([$service::class => $service], $shortener->getServices());
    }

    public function testGetService(): void
    {
        /** @var ShortenerServiceInterface|MockObject $service */
        $service = $this->createStub(ShortenerServiceInterface::class);

        $this->coreParametersHelper
            ->expects($this->once())
            ->method('get')
            ->willReturn($service::class);

        $shortener = new Shortener($this->coreParametersHelper, [$service]);

        $this->assertSame($service, $shortener->getService());
    }

    public function testGetServiceThrowsException(): void
    {
        $shortener = new Shortener($this->coreParametersHelper);

        $this->expectException(\InvalidArgumentException::class);

        $shortener->getService();
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

        $shortener = new Shortener($this->coreParametersHelper, [$service]);

        $this->assertSame($shortUrl, $shortener->shortenUrl($url));
    }

    public function testGetEnabledServices(): void
    {
        $enabledService = new class() implements ShortenerServiceInterface {
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

        $disabledService = new class() implements ShortenerServiceInterface {
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

        $shortener = new Shortener($this->coreParametersHelper, [$enabledService, $disabledService]);

        $this->assertSame([$enabledService::class => $enabledService], $shortener->getEnabledServices());
    }
}
