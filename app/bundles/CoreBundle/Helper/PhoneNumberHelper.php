<?php

namespace Mautic\CoreBundle\Helper;

use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

class PhoneNumberHelper
{
    /**
     * @param int $format
     *
     * @return string
     */
    public function format($number, $format = PhoneNumberFormat::E164)
    {
        $phoneUtil   = PhoneNumberUtil::getInstance();
        $phoneNumber = $phoneUtil->parse($number, 'US');

        return $phoneUtil->format($phoneNumber, $format);
    }

    public function getFormattedNumberList($number): array
    {
        return array_unique(
            [
                $number,
                $this->format($number, PhoneNumberFormat::E164),
                $this->formatNumericalNational($number),
                $this->format($number, PhoneNumberFormat::NATIONAL),
                $this->formatDelimitedNational($number),
                $this->format($number, PhoneNumberFormat::INTERNATIONAL),
                $this->formatNumericalInternational($number),
                $this->formatDelimitedNational($number, '.'),
            ]
        );
    }

    public function formatNumericalInternational($number): ?string
    {
        return preg_replace('/[^0-9]/', '', $this->format($number, PhoneNumberFormat::INTERNATIONAL));
    }

    public function formatNumericalNational($number): ?string
    {
        return preg_replace('/[^0-9]/', '', $this->format($number, PhoneNumberFormat::NATIONAL));
    }

    /**
     * @param string $number
     * @param string $delimiter
     */
    public function formatDelimitedNational($number, $delimiter = '-'): ?string
    {
        $national = $this->format($number, PhoneNumberFormat::NATIONAL);
        $national = str_replace([') ', '-'], $delimiter, $national);

        return preg_replace('/[^0-9'.$delimiter.']/', '', $national);
    }
}
