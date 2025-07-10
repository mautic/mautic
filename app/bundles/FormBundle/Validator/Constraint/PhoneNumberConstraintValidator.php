<?php

namespace Mautic\FormBundle\Validator\Constraint;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberUtil;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class PhoneNumberConstraintValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (null === $value || '' === $value) {
            return;
        }

        if (!is_scalar($value) && !(is_object($value) && method_exists($value, '__toString'))) {
            throw new UnexpectedTypeException($value, 'string');
        }

        $phoneUtil = PhoneNumberUtil::getInstance();

        if (false === $value instanceof PhoneNumber) {
            $value = (string) $value;
            try {
                $phoneNumber = $phoneUtil->parse($value, PhoneNumberUtil::UNKNOWN_REGION);
            } catch (NumberParseException) {
                $this->addViolation($value, $constraint);

                return;
            }
        }

        if (false === $phoneUtil->isValidNumber($phoneNumber)) {
            $this->addViolation($value, $constraint);

            return;
        }
    }

    /**
     * Add a violation.
     *
     * @param mixed      $value      the value that should be validated
     * @param Constraint $constraint the constraint for the validation
     */
    private function addViolation($value, Constraint $constraint): void
    {
        $this->context->addViolation(
            $constraint->getMessage(),
            ['{{ value }}' => $value]
        );
    }
}
