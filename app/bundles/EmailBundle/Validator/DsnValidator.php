<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Validator;

use Mautic\EmailBundle\Validator\Dsn as DsnConstraint;
use Symfony\Component\Mailer\Exception\ExceptionInterface;
use Symfony\Component\Mailer\Exception\InvalidArgumentException;
use Symfony\Component\Mailer\Exception\UnsupportedSchemeException;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\Dsn as MailerDsn;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class DsnValidator extends ConstraintValidator
{
    public function __construct(private Transport $transport)
    {
    }

    public function validate($value, Constraint $constraint): void
    {
        if (!is_string($value)) {
            throw new UnexpectedTypeException($value, 'string');
        }

        if (!$constraint instanceof DsnConstraint) {
            throw new UnexpectedTypeException($constraint, DsnConstraint::class);
        }

        if (!$value) {
            return;
        }

        try {
            $dsn = MailerDsn::fromString($value);
        } catch (InvalidArgumentException) {
            $this->context->addViolation('mautic.email.dsn.invalid_dsn');

            return;
        }

        try {
            $this->transport->fromDsnObject($dsn);
        } catch (UnsupportedSchemeException) {
            $this->context->addViolation('mautic.email.dsn.unsupported_scheme');
        } catch (ExceptionInterface) {
            $this->context->addViolation('mautic.email.dsn.invalid_dsn');
        }
    }
}
