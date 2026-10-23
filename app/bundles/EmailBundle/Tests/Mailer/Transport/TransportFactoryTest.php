<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Mailer\Transport;

use Mautic\EmailBundle\Mailer\Transport\TransportFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Exception\IncompleteDsnException;
use Symfony\Component\Mailer\Exception\InvalidArgumentException;
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

        $this->assertSame('SG.test', $factory->capturedDsn->getUser());
        $this->assertNull($factory->capturedDsn->getPassword());
        $this->assertSame('eu', $factory->capturedDsn->getOption('region'));
    }

    public function testExistingCredentialsAreNotChanged(): void
    {
        $factory   = new CapturingTransportFactory('future+api', requiresUser: true);
        $transport = new TransportFactory(new SymfonyTransportFactory([$factory]));

        $transport->fromString('future+api://user:secret@default');

        $this->assertSame('user', $factory->capturedDsn->getUser());
        $this->assertSame('secret', $factory->capturedDsn->getPassword());
    }

    public function testLiteralColonInPasswordOnlyApiKeyIsPreserved(): void
    {
        $factory   = new CapturingTransportFactory('future+api', requiresUser: true);
        $transport = new TransportFactory(new SymfonyTransportFactory([$factory]));

        $transport->fromString('future+api://:part1:part2@default');

        $this->assertSame('part1:part2', $factory->capturedDsn->getUser());
        $this->assertNull($factory->capturedDsn->getPassword());
    }

    public function testPasswordOnlyApiKeyPreservesSpacesAndPlusSigns(): void
    {
        $factory   = new CapturingTransportFactory('future+api', requiresUser: true);
        $transport = new TransportFactory(new SymfonyTransportFactory([$factory]));

        $transport->fromString('future+api://:part%20one%2Btwo@default');

        $this->assertSame('part one+two', $factory->capturedDsn->getUser());
        $this->assertNull($factory->capturedDsn->getPassword());
    }

    public function testPasswordOnlyApiKeyPreservesAnUnescapedPlusSign(): void
    {
        $factory   = new CapturingTransportFactory('future+api', requiresUser: true);
        $transport = new TransportFactory(new SymfonyTransportFactory([$factory]));

        $transport->fromString('future+api://:part+one@default');

        $this->assertSame('part+one', $factory->capturedDsn->getUser());
    }

    public function testPasswordOnlyApiKeyPreservesZero(): void
    {
        $factory   = new CapturingTransportFactory('future+api', requiresUser: true);
        $transport = new TransportFactory(new SymfonyTransportFactory([$factory]));

        $transport->fromString('future+api://:0@default');

        $this->assertSame('0', $factory->capturedDsn->getUser());
        $this->assertNull($factory->capturedDsn->getPassword());
    }

    public function testSmtpCredentialsAreNotChanged(): void
    {
        $factory   = new CapturingTransportFactory('smtp');
        $transport = new TransportFactory(new SymfonyTransportFactory([$factory]));

        $transport->fromString('smtp://:secret@example.test');

        $this->assertNull($factory->capturedDsn->getUser());
        $this->assertSame('secret', $factory->capturedDsn->getPassword());
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

        $this->assertSame('SG.test', $factory->capturedDsn->getUser());
        $this->assertNull($factory->capturedDsn->getPassword());
    }

    public function testPasswordOnlyApiDsnWorksInsideFailoverTransport(): void
    {
        $factory   = new CapturingTransportFactory('future+api', requiresUser: true);
        $transport = new TransportFactory(new SymfonyTransportFactory([$factory, new CapturingTransportFactory('smtp')]));

        $result = $transport->fromString('failover(future+api://:SG.test@default smtp://relay.test)');

        $this->assertInstanceOf(FailoverTransport::class, $result);
        $this->assertSame('SG.test', $factory->capturedDsn->getUser());
        $this->assertNull($factory->capturedDsn->getPassword());
    }

    public function testPasswordOnlyApiDsnWorksInsideRoundRobinTransport(): void
    {
        $factory   = new CapturingTransportFactory('future+api', requiresUser: true);
        $transport = new TransportFactory(new SymfonyTransportFactory([$factory, new CapturingTransportFactory('smtp')]));

        $result = $transport->fromString('roundrobin(future+api://:SG.test@default smtp://relay.test)?retry_period=90');

        $this->assertInstanceOf(RoundRobinTransport::class, $result);
        $this->assertSame('SG.test', $factory->capturedDsn->getUser());
        $this->assertNull($factory->capturedDsn->getPassword());
    }

    public function testCompoundDsnRejectsTrailingContent(): void
    {
        $factory   = new CapturingTransportFactory('future+api', requiresUser: true);
        $transport = new TransportFactory(new SymfonyTransportFactory([$factory]));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('garbage at the end');
        $transport->fromString('failover(future+api://:SG.test@default)?unexpected=true');
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
