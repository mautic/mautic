<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Helper;

use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

class PhoneNumberHelper
{
    /**
     * Format a phone number.
     *
     * @param string|int $number
     * @param int        $format
     *
     * @return string
     */
    public function format($number, $format = PhoneNumberFormat::E164)
    {
        $phoneUtil   = PhoneNumberUtil::getInstance();
        $phoneNumber = $phoneUtil->parse($number, 'US');

        return $phoneUtil->format($phoneNumber, $format);
    }
}
