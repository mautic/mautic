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
use libphonenumber\PhoneNumber;
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

        if (false === $value instanceof PhoneNumber) {
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
        $i = $constraint->getType();
        if ($i === PhoneNumberType::FIXED_LINE) {
            $validTypes = [PhoneNumberType::FIXED_LINE, PhoneNumberType::FIXED_LINE_OR_MOBILE];
        } elseif ($i === PhoneNumberType::MOBILE) {
            $validTypes = [PhoneNumberType::MOBILE, PhoneNumberType::FIXED_LINE_OR_MOBILE];
        } elseif ($i === PhoneNumberType::PAGER) {
            $validTypes = [PhoneNumberType::PAGER];
        } elseif ($i === PhoneNumberType::PERSONAL_NUMBER) {
            $validTypes = [PhoneNumberType::PERSONAL_NUMBER];
        } elseif ($i === PhoneNumberType::PREMIUM_RATE) {
            $validTypes = [PhoneNumberType::PREMIUM_RATE];
        } elseif ($i === PhoneNumberType::SHARED_COST) {
            $validTypes = [PhoneNumberType::SHARED_COST];
        } elseif ($i === PhoneNumberType::TOLL_FREE) {
            $validTypes = [PhoneNumberType::TOLL_FREE];
        } elseif ($i === PhoneNumberType::UAN) {
            $validTypes = [PhoneNumberType::UAN];
        } elseif ($i == PhoneNumberType::VOIP) {
            $validTypes = [PhoneNumberType::VOIP];
        } elseif ($i === PhoneNumberType::VOICEMAIL) {
            $validTypes = [PhoneNumberType::VOICEMAIL];
        } else {
            $validTypes = [];
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
