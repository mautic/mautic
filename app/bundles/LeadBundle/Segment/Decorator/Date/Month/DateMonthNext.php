<?php

namespace Mautic\LeadBundle\Segment\Decorator\Date\Month;

use Mautic\CoreBundle\Helper\DateTimeHelper;

class DateMonthNext extends DateMonthAbstract
{
    public const MIDNIGHT_FIRST_DAY_OF_NEXT_MONTH = 'midnight first day of next month';

    /**
     * {@inheritdoc}
     */
    protected function modifyBaseDate(DateTimeHelper $dateTimeHelper)
    {
        $dateTimeHelper->setDateTime(self::MIDNIGHT_FIRST_DAY_OF_NEXT_MONTH, null);
    }
}
