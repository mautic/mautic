<?php

namespace Mautic\LeadBundle\Segment\Decorator\Date\Week;

use Mautic\CoreBundle\Helper\DateTimeHelper;

class DateWeekNext extends DateWeekAbstract
{
    public const MIDNIGHT_MONDAY_NEXT_WEEK = 'midnight monday next week';

    /**
     * {@inheritdoc}
     */
    protected function modifyBaseDate(DateTimeHelper $dateTimeHelper)
    {
        $dateTimeHelper->setDateTime(self::MIDNIGHT_MONDAY_NEXT_WEEK, null);
    }
}
