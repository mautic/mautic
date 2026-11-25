<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\IpLookup;

use Mautic\CoreBundle\IpLookup\MaxmindDownloadLookup;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class MaxmindDownloadLookupTest extends TestCase
{
    // MaxMind DB data types, see https://maxmind.github.io/MaxMind-DB/
    private const TYPE_UTF8_STRING = 2;

    private const TYPE_DOUBLE = 3;

    private const TYPE_UINT16 = 5;

    private const TYPE_UINT32 = 6;

    private const TYPE_MAP = 7;

    private const TYPE_UINT64 = 9;

    private const TYPE_ARRAY = 11;

    #[DataProvider('provideMissingOrWrongAuth')]
    public function testNoOrWrongAuth(?string $auth): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('warning')
            ->with('MaxMind license key is required.');

        $lookup = new MaxmindDownloadLookup($auth, logger: $logger);

        $this->assertSame('', $lookup->getRemoteDateStoreDownloadUrl());
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

        $this->assertSame('https://'.$auth.'@download.maxmind.com/geoip/databases/GeoLite2-City/download?suffix=tar.gz', $lookup->getRemoteDateStoreDownloadUrl());
    }

    public static function provideProperAuth(): \Generator
    {
        yield 'No underscore auth' => ['12123:passwd'];
        yield 'Auth with underscore password' => ['12123:license_key_part'];
    }

    public function testLookupReadsDetailsFromTheDatabase(): void
    {
        $baseDir = sys_get_temp_dir().'/mautic-maxmind-'.bin2hex(random_bytes(4));
        mkdir($baseDir.'/cache', 0777, true);
        mkdir($baseDir.'/ip_data');
        $databaseFile = $baseDir.'/ip_data/GeoLite2-City.mmdb';
        file_put_contents($databaseFile, $this->buildCityDatabase([
            'city'     => ['names' => ['en' => 'Berlin']],
            'country'  => ['names' => ['en' => 'Germany']],
            'location' => ['latitude' => 52.5, 'longitude' => 13.4, 'time_zone' => 'Europe/Berlin'],
            'postal'   => ['code' => '10115'],
        ]));

        try {
            $details = (new MaxmindDownloadLookup(null, null, $baseDir.'/cache'))
                ->setIpAddress('192.0.2.1')
                ->getDetails();
        } finally {
            unlink($databaseFile);
            rmdir($baseDir.'/ip_data');
            rmdir($baseDir.'/cache');
            rmdir($baseDir);
        }

        $this->assertSame('Berlin', $details['city']);
        $this->assertSame('Germany', $details['country']);
        $this->assertEqualsWithDelta(52.5, $details['latitude'], PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(13.4, $details['longitude'], PHP_FLOAT_EPSILON);
        $this->assertSame('Europe/Berlin', $details['timezone']);
        $this->assertSame('10115', $details['zipcode']);
    }

    /**
     * Builds a MaxMind DB file whose single search tree node maps every IPv4 address to $record.
     *
     * @see https://maxmind.github.io/MaxMind-DB/
     *
     * @param array<string, array<string, array<string, string>|float|string>> $record
     */
    private function buildCityDatabase(array $record): string
    {
        $dataPointer = pack('N', 1 + 16); // node count + data section separator size
        $searchTree  = substr($dataPointer, 1).substr($dataPointer, 1); // one node with two 24-bit records

        return $searchTree
            .str_repeat("\0", 16)
            .$this->encode($record)
            ."\xAB\xCD\xEFMaxMind.com"
            .$this->encodeMetadata();
    }

    private function encodeMetadata(): string
    {
        // The maxminddb C extension rejects metadata integers that do not have the type the spec gives them
        $integers = [
            'binary_format_major_version' => [2, self::TYPE_UINT16],
            'binary_format_minor_version' => [0, self::TYPE_UINT16],
            'build_epoch'                 => [1_700_000_000, self::TYPE_UINT64],
            'ip_version'                  => [4, self::TYPE_UINT16],
            'node_count'                  => [1, self::TYPE_UINT32],
            'record_size'                 => [24, self::TYPE_UINT16],
        ];
        $others = [
            'database_type' => 'GeoLite2-City',
            'description'   => ['en' => 'Test database'],
            'languages'     => ['en'],
        ];

        $encoded = '';
        foreach ($integers as $key => [$value, $type]) {
            $encoded .= $this->encode($key).$this->encodeUnsigned($value, $type);
        }
        foreach ($others as $key => $value) {
            $encoded .= $this->encode($key).$this->encode($value);
        }

        return $this->controlByte(self::TYPE_MAP, count($integers) + count($others)).$encoded;
    }

    /**
     * @param array<array-key, mixed>|string|float $value
     */
    private function encode(array|string|float $value): string
    {
        if (is_string($value)) {
            return $this->controlByte(self::TYPE_UTF8_STRING, strlen($value)).$value;
        }

        if (is_float($value)) {
            return $this->controlByte(self::TYPE_DOUBLE, 8).pack('E', $value);
        }

        $encoded = '';
        foreach ($value as $key => $item) {
            $encoded .= (array_is_list($value) ? '' : $this->encode((string) $key)).$this->encode($item);
        }

        return $this->controlByte(array_is_list($value) ? self::TYPE_ARRAY : self::TYPE_MAP, count($value)).$encoded;
    }

    private function encodeUnsigned(int $value, int $type): string
    {
        $bytes = ltrim(pack('J', $value), "\0");

        return $this->controlByte($type, strlen($bytes)).$bytes;
    }

    private function controlByte(int $type, int $size): string
    {
        // Types above 7 are stored in an extended type byte
        return $type <= 7 ? chr($type << 5 | $size) : chr($size).chr($type - 7);
    }
}
