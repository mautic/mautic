<?php

namespace Mautic\LeadBundle\Segment\Decorator\Date\Week;

use Mautic\CoreBundle\Helper\DateTimeHelper;

class DateWeekLast extends DateWeekAbstract
{
    public const MIDNIGHT_MONDAY_LAST_WEEK = 'midnight monday last week';

    /**
     * {@inheritdoc}
     */
    protected function modifyBaseDate(DateTimeHelper $dateTimeHelper)
    {
        $dateTimeHelper->setDateTime(self::MIDNIGHT_MONDAY_LAST_WEEK, null);
    }
}
