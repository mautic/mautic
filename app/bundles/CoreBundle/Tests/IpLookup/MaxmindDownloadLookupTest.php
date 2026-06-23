<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\IpLookup;

use Mautic\CoreBundle\IpLookup\MaxmindDownloadLookup;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class MaxmindDownloadLookupTest extends TestCase
{
    #[DataProvider('provideMissingOrWrongAuth')]
    public function testNoOrWrongAuth(?string $auth): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('warning')
            ->with('MaxMind license key is required.');

        $lookup = new MaxmindDownloadLookup($auth, logger: $logger);

        self::assertSame('', $lookup->getRemoteDateStoreDownloadUrl());
    }

    public static function provideMissingOrWrongAuth(): \Generator
    {
        yield 'No auth' => [null];
        yield 'Wrong auth format' => ['something'];
        yield 'Wrong Account ID' => ['aaa:thepw'];
        yield 'Wrong License key' => ['aaa:thepw!'];
    }

    public function testProperAuth(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())
            ->method('warning');

        $lookup = new MaxmindDownloadLookup('12123:passwd', logger: $logger);

        self::assertSame('https://12123:passwd@download.maxmind.com/geoip/databases/GeoLite2-City/download?suffix=tar.gz', $lookup->getRemoteDateStoreDownloadUrl());
    }
}
