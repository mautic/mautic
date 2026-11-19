<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Validator;

use Mautic\EmailBundle\Helper\EmailAddressLinkMatcher;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class EmailAddressMatchesLinkValidator extends ConstraintValidator
{
    public function __construct(private readonly EmailAddressLinkMatcher $emailAddressLinkMatcher)
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof EmailAddressMatchesLink) {
            throw new UnexpectedTypeException($constraint, EmailAddressMatchesLink::class);
        }

        if (!is_string($value) || '' === $value) {
            return;
        }

        if ($this->emailAddressLinkMatcher->matchesLink($value, $constraint->secretHash, $constraint->statEmailAddress)) {
            return;
        }

        $this->context->buildViolation($constraint->message)->addViolation();
    }
}
