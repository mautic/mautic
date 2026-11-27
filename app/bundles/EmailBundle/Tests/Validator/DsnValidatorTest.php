<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Validator;

use Mautic\EmailBundle\Mailer\Transport\TransportFactory;
use Mautic\EmailBundle\Validator\Dsn;
use Mautic\EmailBundle\Validator\DsnValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Exception\IncompleteDsnException;
use Symfony\Component\Mailer\Transport as SymfonyTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn as MailerDsn;
use Symfony\Component\Mailer\Transport\NullTransport;
use Symfony\Component\Mailer\Transport\TransportFactoryInterface;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final class DsnValidatorTest extends TestCase
{
    public function testPasswordOnlyApiDsnIsValidThroughTheValidator(): void
    {
        $factory = new ValidatorCapturingTransportFactory('sendgrid+api', requiresUser: true);
        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->never())->method('addViolation');

        $validator = new DsnValidator(new TransportFactory(new SymfonyTransportFactory([$factory])));
        $validator->initialize($context);
        $validator->validate('sendgrid+api://:SG.test@default', new Dsn());

        $this->assertSame('SG.test', $factory->capturedDsn->getUser());
        $this->assertNull($factory->capturedDsn->getPassword());
    }

    public function testTwoCredentialApiDsnRemainsInvalidThroughTheValidator(): void
    {
        $factory = new ValidatorCapturingTransportFactory('ses+api', requiresUser: true, requiresPassword: true);
        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->once())->method('addViolation');

        $validator = new DsnValidator(new TransportFactory(new SymfonyTransportFactory([$factory])));
        $validator->initialize($context);
        $validator->validate('ses+api://:secret@default', new Dsn());
    }
}

final class ValidatorCapturingTransportFactory implements TransportFactoryInterface
{
    public MailerDsn $capturedDsn;

    public function __construct(
        private readonly string $scheme,
        private readonly bool $requiresUser = false,
        private readonly bool $requiresPassword = false,
    ) {
    }

    public function create(MailerDsn $dsn): TransportInterface
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

    public function supports(MailerDsn $dsn): bool
    {
        return $dsn->getScheme() === $this->scheme;
    }
}
