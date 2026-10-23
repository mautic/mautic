<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Validator;

use Mautic\EmailBundle\Mailer\Transport\TransportFactory;
use Mautic\EmailBundle\Validator\Dsn;
use Mautic\EmailBundle\Validator\DsnValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Transport\NullTransport;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final class DsnValidatorTest extends TestCase
{
    public function testPasswordOnlyApiDsnUsesStringValidationPath(): void
    {
        $dsn               = 'sendgrid+api://:SG.test@default';
        $transportFactory  = $this->createMock(TransportFactory::class);
        $transportFactory->expects(self::once())
            ->method('fromStringForValidation')
            ->with($dsn)
            ->willReturn(new NullTransport());

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects(self::never())->method('addViolation');

        $validator = new DsnValidator($transportFactory);
        $validator->initialize($context);
        $validator->validate($dsn, new Dsn());
    }
}
