<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Helper;

use Mautic\CoreBundle\Helper\Dsn\Dsn;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class DsnTest extends TestCase
{
    public function testGettersAndSetters(): void
    {
        $dsn = new Dsn('scheme', 'localhost', 'user', 'password', 3300, 'path', ['ttl' => '200']);
        Assert::assertSame('scheme://user:password@localhost:3300/path?ttl=200', (string) $dsn);

        $newDsn = $dsn->setScheme('mysql');
        Assert::assertNotSame($newDsn, $dsn);
        Assert::assertSame('scheme://user:password@localhost:3300/path?ttl=200', (string) $dsn);
        Assert::assertSame('mysql://user:password@localhost:3300/path?ttl=200', (string) $newDsn);
        Assert::assertSame('mysql', $newDsn->getScheme());

        $newDsn = $dsn->setHost('db');
        Assert::assertNotSame($newDsn, $dsn);
        Assert::assertSame('scheme://user:password@localhost:3300/path?ttl=200', (string) $dsn);
        Assert::assertSame('scheme://user:password@db:3300/path?ttl=200', (string) $newDsn);
        Assert::assertSame('db', $newDsn->getHost());

        $newDsn = $dsn->setUser('john');
        Assert::assertNotSame($newDsn, $dsn);
        Assert::assertSame('scheme://user:password@localhost:3300/path?ttl=200', (string) $dsn);
        Assert::assertSame('scheme://john:password@localhost:3300/path?ttl=200', (string) $newDsn);
        Assert::assertSame('john', $newDsn->getUser());

        $newDsn = $dsn->setPassword('secret');
        Assert::assertNotSame($newDsn, $dsn);
        Assert::assertSame('scheme://user:password@localhost:3300/path?ttl=200', (string) $dsn);
        Assert::assertSame('scheme://user:secret@localhost:3300/path?ttl=200', (string) $newDsn);
        Assert::assertSame('secret', $newDsn->getPassword());

        $newDsn = $dsn->setPort(3301);
        Assert::assertNotSame($newDsn, $dsn);
        Assert::assertSame('scheme://user:password@localhost:3300/path?ttl=200', (string) $dsn);
        Assert::assertSame('scheme://user:password@localhost:3301/path?ttl=200', (string) $newDsn);
        Assert::assertSame(3301, $newDsn->getPort());

        $newDsn = $dsn->setPath('folder');
        Assert::assertNotSame($newDsn, $dsn);
        Assert::assertSame('scheme://user:password@localhost:3300/path?ttl=200', (string) $dsn);
        Assert::assertSame('scheme://user:password@localhost:3300/folder?ttl=200', (string) $newDsn);
        Assert::assertSame('folder', $newDsn->getPath());

        $newDsn = $dsn->setOptions(['ttl' => '300', 'timeout' => '10']);
        Assert::assertNotSame($newDsn, $dsn);
        Assert::assertSame('scheme://user:password@localhost:3300/path?ttl=200', (string) $dsn);
        Assert::assertSame('scheme://user:password@localhost:3300/path?ttl=300&timeout=10', (string) $newDsn);
        Assert::assertSame(['ttl' => '300', 'timeout' => '10'], $newDsn->getOptions());
        Assert::assertSame('300', $newDsn->getOption('ttl'));
        Assert::assertSame('10', $newDsn->getOption('timeout'));
    }

    /**
     * @dataProvider dataInvalidFromString
     */
    public function testInvalidFromString(string $dsn, string $exceptionMessage): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($exceptionMessage);
        Dsn::fromString($dsn);
    }

    /**
     * @return iterable<string, array<string|string>>
     */
    public function dataInvalidFromString(): iterable
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
        Assert::assertSame('sync://', (string) Dsn::fromString('sync://'));
    }

    public function testFromString(): void
    {
        Assert::assertSame('scheme://user:password@localhost:3300/path?ttl=300&timeout=10', (string) Dsn::fromString('scheme://user:password@localhost:3300/path?ttl=300&timeout=10'));
    }

    /**
     * @dataProvider dataToString
     */
    public function testToString(Dsn $dsn, string $dsnString): void
    {
        Assert::assertSame($dsnString, (string) $dsn);
    }

    /**
     * @return iterable<string, array<Dsn|string>>
     */
    public function dataToString(): iterable
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
        Assert::assertSame('scheme://'.urlencode('us+@$#/:*!er').':'.urlencode('pass+@$#/:*!word').'@'.urlencode('local+@$#/:*!host').':3300/'.urlencode('pa+@$#/:*!th').'?type='.urlencode('ty+@$#/:*!pe'), (string) $dsn);

        $dsnFromString = Dsn::fromString((string) $dsn);
        Assert::assertSame('local+@$#/:*!host', $dsnFromString->getHost());
        Assert::assertSame('us+@$#/:*!er', $dsnFromString->getUser());
        Assert::assertSame('pass+@$#/:*!word', $dsnFromString->getPassword());
        Assert::assertSame('pa+@$#/:*!th', $dsnFromString->getPath());
        Assert::assertSame('ty+@$#/:*!pe', $dsnFromString->getOption('type'));
    }
}
