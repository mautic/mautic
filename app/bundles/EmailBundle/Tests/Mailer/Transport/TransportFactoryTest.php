<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Mailer\Transport;

use Mautic\EmailBundle\Mailer\Transport\TransportFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Transport as SymfonyTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\NullTransport;
use Symfony\Component\Mailer\Transport\TransportFactoryInterface;
use Symfony\Component\Mailer\Transport\TransportInterface;

final class TransportFactoryTest extends TestCase
{
    #[DataProvider('sendgridSchemes')]
    public function testPasswordOnlyApiDsnUsesThePasswordAsTheApiKey(string $scheme): void
    {
        $capturedDsn = null;
        $factory     = new CapturingTransportFactory($capturedDsn, $scheme);
        $transport   = new TransportFactory(new SymfonyTransportFactory([$factory]));

        $transport->fromString(sprintf('%s://:SG.test@default?region=eu', $scheme));

        self::assertNotNull($capturedDsn);
        self::assertSame('SG.test', $capturedDsn->getUser());
        self::assertNull($capturedDsn->getPassword());
        self::assertSame('eu', $capturedDsn->getOption('region'));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function sendgridSchemes(): iterable
    {
        yield 'sendgrid' => ['sendgrid'];
        yield 'sendgrid api' => ['sendgrid+api'];
        yield 'mailgun api' => ['mailgun+api'];
        yield 'custom api bridge' => ['custom+api'];
    }

    public function testExistingSendgridUserIsNotChanged(): void
    {
        $capturedDsn = null;
        $factory     = new CapturingTransportFactory($capturedDsn, 'sendgrid+api');
        $transport   = new TransportFactory(new SymfonyTransportFactory([$factory]));

        $transport->fromString('sendgrid+api://SG.user:SG.secret@default');

        self::assertNotNull($capturedDsn);
        self::assertSame('SG.user', $capturedDsn->getUser());
        self::assertSame('SG.secret', $capturedDsn->getPassword());
    }

    public function testSendgridSmtpCredentialsAreNotChanged(): void
    {
        $capturedDsn = null;
        $factory     = new CapturingTransportFactory($capturedDsn, 'sendgrid+smtp');
        $transport   = new TransportFactory(new SymfonyTransportFactory([$factory]));

        $transport->fromString('sendgrid+smtp://:SG.secret@default');

        self::assertNotNull($capturedDsn);
        self::assertNull($capturedDsn->getUser());
        self::assertSame('SG.secret', $capturedDsn->getPassword());
    }

    public function testPasswordOnlySendgridDsnForValidationUsesThePasswordAsTheApiKey(): void
    {
        $capturedDsn = null;
        $factory     = new CapturingTransportFactory($capturedDsn, 'sendgrid+api');
        $transport   = new TransportFactory(new SymfonyTransportFactory([$factory]));

        $transport->fromStringForValidation('sendgrid+api://:SG.test@default?region=eu');

        self::assertNotNull($capturedDsn);
        self::assertSame('SG.test', $capturedDsn->getUser());
        self::assertNull($capturedDsn->getPassword());
        self::assertSame('eu', $capturedDsn->getOption('region'));
    }

    public function testNonSendgridDsnIsNotChanged(): void
    {
        $capturedDsn = null;
        $factory     = new CapturingTransportFactory($capturedDsn, 'smtp');
        $transport   = new TransportFactory(new SymfonyTransportFactory([$factory]));

        $transport->fromString('smtp://:secret@example.test');

        self::assertNotNull($capturedDsn);
        self::assertNull($capturedDsn->getUser());
        self::assertSame('secret', $capturedDsn->getPassword());
    }
}

final class CapturingTransportFactory implements TransportFactoryInterface
{
    public function __construct(
        private ?Dsn &$capturedDsn,
        private readonly string $scheme,
    ) {
    }

    public function create(Dsn $dsn): TransportInterface
    {
        $this->capturedDsn = $dsn;

        return new NullTransport();
    }

    public function supports(Dsn $dsn): bool
    {
        return $dsn->getScheme() === $this->scheme;
    }
}
