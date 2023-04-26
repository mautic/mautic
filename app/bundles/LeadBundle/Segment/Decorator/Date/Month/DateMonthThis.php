<?php

namespace Mautic\LeadBundle\Segment\Decorator\Date\Month;

use Mautic\CoreBundle\Helper\DateTimeHelper;

class DateMonthThis extends DateMonthAbstract
{
    public const MIDNIGHT_FIRST_DAY_OF_THIS_MONTH = 'midnight first day of this month';

    /**
     * {@inheritdoc}
     */
    protected function modifyBaseDate(DateTimeHelper $dateTimeHelper)
    {
        $dateTimeHelper->setDateTime(self::MIDNIGHT_FIRST_DAY_OF_THIS_MONTH, null);
    }
}
