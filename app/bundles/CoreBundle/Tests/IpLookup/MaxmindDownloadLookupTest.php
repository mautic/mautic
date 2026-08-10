<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\IpLookup;

use Mautic\CoreBundle\IpLookup\MaxmindDownloadLookup;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class MaxmindDownloadLookupTest extends TestCase
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

    #[DataProvider('provideProperAuth')]
    public function testProperAuth(string $auth): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())
            ->method('warning');

        $lookup = new MaxmindDownloadLookup($auth, logger: $logger);

        self::assertSame('https://'.$auth.'@download.maxmind.com/geoip/databases/GeoLite2-City/download?suffix=tar.gz', $lookup->getRemoteDateStoreDownloadUrl());
    }

    public static function provideProperAuth(): \Generator
    {
        yield 'No underscore auth' => ['12123:passwd'];
        yield 'Auth with underscore password' => ['12123:license_key_part'];
    }
}
