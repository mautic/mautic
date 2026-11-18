<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Helper\Dsn;

use Mautic\CoreBundle\Helper\Dsn\Dsn;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

#[CoversClass(Dsn::class)]
final class DsnTest extends TestCase
{
    #[TestDox('A percent-encoded path segment such as the RabbitMQ vhost "/" survives a parse and a rebuild')]
    public function testEncodedPathSegmentIsKept(): void
    {
        $dsn = Dsn::fromString('amqp://user:pass@localhost:5672/%2f/emails');

        $this->assertSame('%2f/emails', $dsn->getPath());
        $this->assertSame('amqp://user:pass@localhost:5672/%2F/emails', (string) $dsn);
    }

    #[TestDox('A path with several segments is kept as is')]
    public function testPlainPathIsKept(): void
    {
        $dsn = Dsn::fromString('s3://key:secret@default/bucket/name/two?region=eu-west-1');

        $this->assertSame('bucket/name/two', $dsn->getPath());
        $this->assertSame('s3://key:secret@default/bucket/name/two?region=eu-west-1', (string) $dsn);
    }

    /**
     * @return iterable<string, array{string|null, string}>
     */
    public static function pathProvider(): iterable
    {
        yield 'no path' => [null, 'sync://default'];
        yield 'plain' => ['emails', 'sync://default/emails'];
        yield 'already encoded' => ['%2f/emails', 'sync://default/%2F/emails'];
        yield 'needs encoding' => ['my bucket/two', 'sync://default/my%20bucket/two'];
    }

    #[DataProvider('pathProvider')]
    #[TestDox('The path given to the constructor is encoded per segment when the DSN is rebuilt')]
    public function testConstructorPathIsEncodedPerSegment(?string $path, string $expected): void
    {
        $this->assertSame($expected, (string) new Dsn('sync', 'default', null, null, null, $path));
    }

    #[TestDox('Parsing the rebuilt DSN gives the same path again')]
    public function testRoundTripIsStable(): void
    {
        $first  = Dsn::fromString('amqp://user:pass@localhost:5672/%2f/emails?heartbeat=0');
        $second = Dsn::fromString((string) $first);

        $this->assertSame((string) $first, (string) $second);
        $this->assertSame('%2F/emails', $second->getPath());
    }
}
