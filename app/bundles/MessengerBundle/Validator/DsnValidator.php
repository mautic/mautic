<?php

declare(strict_types=1);

namespace Mautic\MessengerBundle\Validator;

use Mautic\CoreBundle\Helper\Dsn\Dsn as CoreDsn;
use Mautic\MessengerBundle\Validator\Dsn as DsnConstraint;
use Symfony\Component\Messenger\Transport\TransportFactory;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * @deprecated since Mautic 5.0, to be removed in 6.0 with no replacement.
 */
class DsnValidator extends ConstraintValidator
{
    public function __construct(
        private TransportFactory $transportFactory,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
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
            $dsn = CoreDsn::fromString($value);
        } catch (\InvalidArgumentException) {
            $this->context->addViolation('mautic.messenger.dsn.invalid_dsn');

            return;
        }

        if (!$this->transportFactory->supports($value, $dsn->getOptions())) {
            $this->context->addViolation('mautic.messenger.dsn.unsupported_scheme');
        }
    }
}
