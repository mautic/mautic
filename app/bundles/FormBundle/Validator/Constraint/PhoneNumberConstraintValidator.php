<?php

/*
 * This file is part of the Symfony2 PhoneNumberBundle.
 *
 * (c) University of Cambridge
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mautic\FormBundle\Validator\Constraint;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumber as PhoneNumberObject;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberType;
use libphonenumber\PhoneNumberUtil;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Phone number validator.
 */
class PhoneNumberConstraintValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (null === $value || '' === $value) {
            return;
        }

        if (!is_scalar($value) && !(is_object($value) && method_exists($value, '__toString'))) {
            throw new UnexpectedTypeException($value, 'string');
        }

        $phoneUtil = PhoneNumberUtil::getInstance();

        if (false === $value instanceof PhoneNumberObject) {
            $value = (string) $value;

            try {
                $phoneNumber = $phoneUtil->parse($value, $constraint->defaultRegion);
            } catch (NumberParseException $e) {
                $this->addViolation($value, $constraint);

                return;
            }
        } else {
            $phoneNumber = $value;
            $value       = $phoneUtil->format($phoneNumber, PhoneNumberFormat::INTERNATIONAL);
        }

        if (false === $phoneUtil->isValidNumber($phoneNumber)) {
            $this->addViolation($value, $constraint);

            return;
        }

        switch ($constraint->getType()) {
            case PhoneNumber::FIXED_LINE:
                $validTypes = [PhoneNumberType::FIXED_LINE, PhoneNumberType::FIXED_LINE_OR_MOBILE];
                break;
            case PhoneNumber::MOBILE:
                $validTypes = [PhoneNumberType::MOBILE, PhoneNumberType::FIXED_LINE_OR_MOBILE];
                break;
            case PhoneNumber::PAGER:
                $validTypes = [PhoneNumberType::PAGER];
                break;
            case PhoneNumber::PERSONAL_NUMBER:
                $validTypes = [PhoneNumberType::PERSONAL_NUMBER];
                break;
            case PhoneNumber::PREMIUM_RATE:
                $validTypes = [PhoneNumberType::PREMIUM_RATE];
                break;
            case PhoneNumber::SHARED_COST:
                $validTypes = [PhoneNumberType::SHARED_COST];
                break;
            case PhoneNumber::TOLL_FREE:
                $validTypes = [PhoneNumberType::TOLL_FREE];
                break;
            case PhoneNumber::UAN:
                $validTypes = [PhoneNumberType::UAN];
                break;
            case PhoneNumber::VOIP:
                $validTypes = [PhoneNumberType::VOIP];
                break;
            case PhoneNumber::VOICEMAIL:
                $validTypes = [PhoneNumberType::VOICEMAIL];
                break;
            default:
                $validTypes = [];
                break;
        }

        if (count($validTypes)) {
            $type = $phoneUtil->getNumberType($phoneNumber);

            if (false === in_array($type, $validTypes)) {
                $this->addViolation($value, $constraint);

                return;
            }
        }
    }

    /**
     * Add a violation.
     *
     * @param mixed      $value      the value that should be validated
     * @param Constraint $constraint the constraint for the validation
     */
    private function addViolation($value, Constraint $constraint)
    {
        $this->context->addViolation(
            $constraint->getMessage(),
            ['{{ type }}' => $constraint->getType(), '{{ value }}' => $value]
        );
    }
}
