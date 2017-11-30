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

use libphonenumber\PhoneNumberUtil;
use Symfony\Component\Validator\Constraint;

/**
 * Phone number constraint.
 *
 * @Annotation
 */
class PhoneNumberConstraint extends Constraint
{
    const ANY             = 'any';
    const FIXED_LINE      = 'fixed_line';
    const MOBILE          = 'mobile';
    const PAGER           = 'pager';
    const PERSONAL_NUMBER = 'personal_number';
    const PREMIUM_RATE    = 'premium_rate';
    const SHARED_COST     = 'shared_cost';
    const TOLL_FREE       = 'toll_free';
    const UAN             = 'uan';
    const VOIP            = 'voip';
    const VOICEMAIL       = 'voicemail';

    public $message       = null;
    public $type          = self::ANY;
    public $defaultRegion = PhoneNumberUtil::UNKNOWN_REGION;

    public function getType()
    {
        switch ($this->type) {
            case self::FIXED_LINE:
            case self::MOBILE:
            case self::PAGER:
            case self::PERSONAL_NUMBER:
            case self::PREMIUM_RATE:
            case self::SHARED_COST:
            case self::TOLL_FREE:
            case self::UAN:
            case self::VOIP:
            case self::VOICEMAIL:
                return $this->type;
        }

        return self::ANY;
    }

    public function getMessage()
    {
        if (null !== $this->message) {
            return $this->message;
        }

        switch ($this->type) {
            case self::FIXED_LINE:
                return 'This value is not a valid fixed-line number.';
            case self::MOBILE:
                return 'This value is not a valid mobile number.';
            case self::PAGER:
                return 'This value is not a valid pager number.';
            case self::PERSONAL_NUMBER:
                return 'This value is not a valid personal number.';
            case self::PREMIUM_RATE:
                return 'This value is not a valid premium-rate number.';
            case self::SHARED_COST:
                return 'This value is not a valid shared-cost number.';
            case self::TOLL_FREE:
                return 'This value is not a valid toll-free number.';
            case self::UAN:
                return 'This value is not a valid UAN.';
            case self::VOIP:
                return 'This value is not a valid VoIP number.';
            case self::VOICEMAIL:
                return 'This value is not a valid voicemail access number.';
        }

        return 'This value is not a valid phone number.';
    }
}
