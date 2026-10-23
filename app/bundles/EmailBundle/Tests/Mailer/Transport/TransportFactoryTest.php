<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Mailer\Transport;

use Mautic\EmailBundle\Mailer\Transport\TransportFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Exception\IncompleteDsnException;
use Symfony\Component\Mailer\Transport as SymfonyTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\FailoverTransport;
use Symfony\Component\Mailer\Transport\NullTransport;
use Symfony\Component\Mailer\Transport\RoundRobinTransport;
use Symfony\Component\Mailer\Transport\TransportFactoryInterface;
use Symfony\Component\Mailer\Transport\TransportInterface;

final class TransportFactoryTest extends TestCase
{
    public function testPasswordOnlyApiDsnUsesThePasswordAsTheApiKey(): void
    {
        $factory   = new CapturingTransportFactory('future+api', requiresUser: true);
        $transport = new TransportFactory(new SymfonyTransportFactory([$factory]));

        $transport->fromString('future+api://:SG.test@default?region=eu');

        self::assertSame('SG.test', $factory->capturedDsn->getUser());
        self::assertNull($factory->capturedDsn->getPassword());
        self::assertSame('eu', $factory->capturedDsn->getOption('region'));
    }

    public function testExistingCredentialsAreNotChanged(): void
    {
        $factory   = new CapturingTransportFactory('future+api', requiresUser: true);
        $transport = new TransportFactory(new SymfonyTransportFactory([$factory]));

        $transport->fromString('future+api://user:secret@default');

        self::assertSame('user', $factory->capturedDsn->getUser());
        self::assertSame('secret', $factory->capturedDsn->getPassword());
    }

    public function testLiteralColonInPasswordOnlyApiKeyIsPreserved(): void
    {
        $factory   = new CapturingTransportFactory('future+api', requiresUser: true);
        $transport = new TransportFactory(new SymfonyTransportFactory([$factory]));

        $transport->fromString('future+api://:part1:part2@default');

        self::assertSame('part1:part2', $factory->capturedDsn->getUser());
        self::assertNull($factory->capturedDsn->getPassword());
    }

    public function testSmtpCredentialsAreNotChanged(): void
    {
        $factory   = new CapturingTransportFactory('smtp');
        $transport = new TransportFactory(new SymfonyTransportFactory([$factory]));

        $transport->fromString('smtp://:secret@example.test');

        self::assertNull($factory->capturedDsn->getUser());
        self::assertSame('secret', $factory->capturedDsn->getPassword());
    }

    public function testTwoCredentialApiDsnKeepsItsOriginalError(): void
    {
        $factory   = new CapturingTransportFactory('ses+api', requiresUser: true, requiresPassword: true);
        $transport = new TransportFactory(new SymfonyTransportFactory([$factory]));

        $this->expectException(IncompleteDsnException::class);
        $this->expectExceptionMessage('User is not set');
        $transport->fromString('ses+api://:secret@default');
    }

    public function testPasswordOnlyApiDsnForValidationUsesTheFallback(): void
    {
        $factory   = new CapturingTransportFactory('future+api', requiresUser: true);
        $transport = new TransportFactory(new SymfonyTransportFactory([$factory]));

        $transport->fromStringForValidation('future+api://:SG.test@default?region=eu');

        self::assertSame('SG.test', $factory->capturedDsn->getUser());
        self::assertNull($factory->capturedDsn->getPassword());
    }

    public function testPasswordOnlyApiDsnWorksInsideFailoverTransport(): void
    {
        $factory   = new CapturingTransportFactory('future+api', requiresUser: true);
        $transport = new TransportFactory(new SymfonyTransportFactory([$factory]));

        $result = $transport->fromString('failover(future+api://:SG.test@default smtp://relay.test)');

        self::assertInstanceOf(FailoverTransport::class, $result);
        self::assertSame('SG.test', $factory->capturedDsn->getUser());
        self::assertNull($factory->capturedDsn->getPassword());
    }

    public function testPasswordOnlyApiDsnWorksInsideRoundRobinTransport(): void
    {
        $factory   = new CapturingTransportFactory('future+api', requiresUser: true);
        $transport = new TransportFactory(new SymfonyTransportFactory([$factory]));

        $result = $transport->fromString('roundrobin(future+api://:SG.test@default smtp://relay.test)?retry_period=90');

        self::assertInstanceOf(RoundRobinTransport::class, $result);
        self::assertSame('SG.test', $factory->capturedDsn->getUser());
        self::assertNull($factory->capturedDsn->getPassword());
    }
}

final class CapturingTransportFactory implements TransportFactoryInterface
{
    public Dsn $capturedDsn;

    public function __construct(
        private readonly string $scheme,
        private readonly bool $requiresUser = false,
        private readonly bool $requiresPassword = false,
    ) {
    }

    public function create(Dsn $dsn): TransportInterface
    {
        if ($this->requiresUser && null === $dsn->getUser()) {
            throw new IncompleteDsnException('User is not set');
        }

        if ($this->requiresPassword && null === $dsn->getPassword()) {
            throw new IncompleteDsnException('Password is not set');
        }

        $this->capturedDsn = $dsn;

        return new NullTransport();
    }

    public function supports(Dsn $dsn): bool
    {
        return $dsn->getScheme() === $this->scheme;
    }
}
