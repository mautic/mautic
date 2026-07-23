<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Helper;

use Mautic\CoreBundle\Helper\Dsn\Dsn;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DsnTest extends TestCase
{
    public function testGettersAndSetters(): void
    {
        $dsn = new Dsn('scheme', 'localhost', 'user', 'password', 3300, 'path', ['ttl' => '200']);
        $this->assertSame('scheme://user:password@localhost:3300/path?ttl=200', (string) $dsn);

        $newDsn = $dsn->setScheme('mysql');
        $this->assertNotSame($newDsn, $dsn);
        $this->assertSame('scheme://user:password@localhost:3300/path?ttl=200', (string) $dsn);
        $this->assertSame('mysql://user:password@localhost:3300/path?ttl=200', (string) $newDsn);
        $this->assertSame('mysql', $newDsn->getScheme());

        $newDsn = $dsn->setHost('db');
        $this->assertNotSame($newDsn, $dsn);
        $this->assertSame('scheme://user:password@localhost:3300/path?ttl=200', (string) $dsn);
        $this->assertSame('scheme://user:password@db:3300/path?ttl=200', (string) $newDsn);
        $this->assertSame('db', $newDsn->getHost());

        $newDsn = $dsn->setUser('john');
        $this->assertNotSame($newDsn, $dsn);
        $this->assertSame('scheme://user:password@localhost:3300/path?ttl=200', (string) $dsn);
        $this->assertSame('scheme://john:password@localhost:3300/path?ttl=200', (string) $newDsn);
        $this->assertSame('john', $newDsn->getUser());

        $newDsn = $dsn->setPassword('secret');
        $this->assertNotSame($newDsn, $dsn);
        $this->assertSame('scheme://user:password@localhost:3300/path?ttl=200', (string) $dsn);
        $this->assertSame('scheme://user:secret@localhost:3300/path?ttl=200', (string) $newDsn);
        $this->assertSame('secret', $newDsn->getPassword());

        $newDsn = $dsn->setPort(3301);
        $this->assertNotSame($newDsn, $dsn);
        $this->assertSame('scheme://user:password@localhost:3300/path?ttl=200', (string) $dsn);
        $this->assertSame('scheme://user:password@localhost:3301/path?ttl=200', (string) $newDsn);
        $this->assertSame(3301, $newDsn->getPort());

        $newDsn = $dsn->setPath('folder');
        $this->assertNotSame($newDsn, $dsn);
        $this->assertSame('scheme://user:password@localhost:3300/path?ttl=200', (string) $dsn);
        $this->assertSame('scheme://user:password@localhost:3300/folder?ttl=200', (string) $newDsn);
        $this->assertSame('folder', $newDsn->getPath());

        $newDsn = $dsn->setOptions(['ttl' => '300', 'timeout' => '10']);
        $this->assertNotSame($newDsn, $dsn);
        $this->assertSame('scheme://user:password@localhost:3300/path?ttl=200', (string) $dsn);
        $this->assertSame('scheme://user:password@localhost:3300/path?ttl=300&timeout=10', (string) $newDsn);
        $this->assertSame(['ttl' => '300', 'timeout' => '10'], $newDsn->getOptions());
        $this->assertSame('300', $newDsn->getOption('ttl'));
        $this->assertSame('10', $newDsn->getOption('timeout'));
    }

    #[DataProvider('dataInvalidFromString')]
    public function testInvalidFromString(string $dsn, string $exceptionMessage): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($exceptionMessage);
        Dsn::fromString($dsn);
    }

    /**
     * @return iterable<string, array<string>>
     */
    public static function dataInvalidFromString(): iterable
    {
        yield 'DSN is invalid.' => [
            ':', 'The ":" DSN is invalid.',
        ];

        yield 'DSN must contain a scheme.' => [
            '://host', 'The "://host" DSN must contain a scheme.',
        ];

        yield 'DSN must contain a host.' => [
            'scheme:', 'The "scheme:" DSN must contain a host (use "default" by default).',
        ];
    }

    public function testFromStringAllowedDns(): void
    {
        $this->assertSame('sync://', (string) Dsn::fromString('sync://'));
    }

    public function testFromString(): void
    {
        $this->assertSame('scheme://user:password@localhost:3300/path?ttl=300&timeout=10', (string) Dsn::fromString('scheme://user:password@localhost:3300/path?ttl=300&timeout=10'));
    }

    #[DataProvider('dataToString')]
    public function testToString(Dsn $dsn, string $dsnString): void
    {
        $this->assertSame($dsnString, (string) $dsn);
    }

    /**
     * @return iterable<string, array<Dsn|string>>
     */
    public static function dataToString(): iterable
    {
        yield 'With host.' => [
            new Dsn('smtp', 'host'), 'smtp://host',
        ];

        yield 'With host and user.' => [
            new Dsn('smtp', 'host', 'user'), 'smtp://user@host',
        ];

        yield 'With host, user, password.' => [
            new Dsn('smtp', 'host', 'user', 'password'), 'smtp://user:password@host',
        ];

        yield 'With host, port, user, password.' => [
            new Dsn('smtp', 'host', 'user', 'password', 25), 'smtp://user:password@host:25',
        ];

        yield 'With host, port, path and query.' => [
            new Dsn('smtp', 'host', 'user', 'password', 25, 'test-path', ['encryption' => 'tls', 'auth_mode'=>'login']), 'smtp://user:password@host:25/test-path?encryption=tls&auth_mode=login',
        ];
    }

    public function testToStringUrlEncodesProperly(): void
    {
        $dsn = new Dsn('scheme', 'local+@$#/:*!host', 'us+@$#/:*!er', 'pass+@$#/:*!word', 3300, 'pa+@$#/:*!th', ['type' => 'ty+@$#/:*!pe']);
        $this->assertSame('scheme://'.urlencode('us+@$#/:*!er').':'.urlencode('pass+@$#/:*!word').'@'.urlencode('local+@$#/:*!host').':3300/'.urlencode('pa+@$#/:*!th').'?type='.urlencode('ty+@$#/:*!pe'), (string) $dsn);

        $dsnFromString = Dsn::fromString((string) $dsn);
        $this->assertSame('local+@$#/:*!host', $dsnFromString->getHost());
        $this->assertSame('us+@$#/:*!er', $dsnFromString->getUser());
        $this->assertSame('pass+@$#/:*!word', $dsnFromString->getPassword());
        $this->assertSame('pa+@$#/:*!th', $dsnFromString->getPath());
        $this->assertSame('ty+@$#/:*!pe', $dsnFromString->getOption('type'));
    }
}
