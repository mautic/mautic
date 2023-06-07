<?php

namespace Mautic\LeadBundle\Segment\Decorator\Date\Week;

use Mautic\CoreBundle\Helper\DateTimeHelper;

class DateWeekThis extends DateWeekAbstract
{
    public const MIDNIGHT_MONDAY_THIS_WEEK = 'midnight monday this week';

    /**
     * {@inheritdoc}
     */
    protected function modifyBaseDate(DateTimeHelper $dateTimeHelper)
    {
        $dateTimeHelper->setDateTime(self::MIDNIGHT_MONDAY_THIS_WEEK, null);
    }
}
