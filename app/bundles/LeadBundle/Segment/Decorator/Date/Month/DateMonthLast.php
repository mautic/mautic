<?php

namespace Mautic\LeadBundle\Segment\Decorator\Date\Month;

use Mautic\CoreBundle\Helper\DateTimeHelper;

class DateMonthLast extends DateMonthAbstract
{
    public const MIDNIGHT_FIRST_DAY_OF_LAST_MONTH = 'midnight first day of last month';

    /**
     * {@inheritdoc}
     */
    protected function modifyBaseDate(DateTimeHelper $dateTimeHelper)
    {
        $dateTimeHelper->setDateTime(self::MIDNIGHT_FIRST_DAY_OF_LAST_MONTH, null);
    }
}
