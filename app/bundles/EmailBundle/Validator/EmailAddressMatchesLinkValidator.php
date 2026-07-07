<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Validator;

use Mautic\EmailBundle\Helper\MailHashHelper;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class EmailAddressMatchesLinkValidator extends ConstraintValidator
{
    public function __construct(private MailHashHelper $mailHash)
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

        $matchesSecretHash = $this->mailHash->getEmailHash($value) === $constraint->secretHash;
        $normalizedValue   = strtolower($value);
        if (!$matchesSecretHash && $normalizedValue !== $value) {
            $matchesSecretHash = $this->mailHash->getEmailHash($normalizedValue) === $constraint->secretHash;
        }

        $matchesStatEmail = true;
        if (null !== $constraint->statEmailAddress) {
            $matchesStatEmail  = strtolower($value) === strtolower($constraint->statEmailAddress);
            $matchesSecretHash = $matchesSecretHash
                || $this->mailHash->getEmailHash($constraint->statEmailAddress) === $constraint->secretHash;
        }

        if ($matchesSecretHash && $matchesStatEmail) {
            return;
        }

        $this->context->buildViolation($constraint->message)->addViolation();
    }
}
