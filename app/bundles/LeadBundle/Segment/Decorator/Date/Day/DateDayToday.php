<?php

namespace Mautic\LeadBundle\Segment\Decorator\Date\Day;

use Mautic\CoreBundle\Helper\DateTimeHelper;

final class DateDayToday extends DateDayAbstract
{
    protected function modifyBaseDate(DateTimeHelper $dateTimeHelper): void
    {
        $dateTimeHelper->modify('midnight today');
    }
}
