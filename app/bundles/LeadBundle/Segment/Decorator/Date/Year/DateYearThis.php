<?php

namespace Mautic\LeadBundle\Segment\Decorator\Date\Year;

use Mautic\CoreBundle\Helper\DateTimeHelper;

class DateYearThis extends DateYearAbstract
{
    public const MIDNIGHT_FIRST_DAY_OF_JANUARY_THIS_YEAR = 'midnight first day of January this year';

    /**
     * {@inheritdoc}
     */
    protected function modifyBaseDate(DateTimeHelper $dateTimeHelper)
    {
        $dateTimeHelper->setDateTime(self::MIDNIGHT_FIRST_DAY_OF_JANUARY_THIS_YEAR, null);
    }
}
