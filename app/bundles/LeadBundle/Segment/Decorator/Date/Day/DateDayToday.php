<?php

namespace Mautic\LeadBundle\Segment\Decorator\Date\Day;

use Mautic\CoreBundle\Helper\DateTimeHelper;

class DateDayToday extends DateDayAbstract
{
    public const BASE_DATE = 'midnight today';

    /**
     * {@inheritdoc}
     */
    protected function modifyBaseDate(DateTimeHelper $dateTimeHelper)
    {
        $dateTimeHelper->setDateTime(self::BASE_DATE, null);
    }
}
