<?php

namespace Mautic\LeadBundle\Segment\Decorator\Date\Year;

use Mautic\CoreBundle\Helper\DateTimeHelper;

class DateYearLast extends DateYearAbstract
{
    public const MIDNIGHT_FIRST_DAY_OF_JANUARY_LAST_YEAR = 'midnight first day of January last year';

    /**
     * {@inheritdoc}
     */
    protected function modifyBaseDate(DateTimeHelper $dateTimeHelper)
    {
        $dateTimeHelper->setDateTime(self::MIDNIGHT_FIRST_DAY_OF_JANUARY_LAST_YEAR, null);
    }
}
