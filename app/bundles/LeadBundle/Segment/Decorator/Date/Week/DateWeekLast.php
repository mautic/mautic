<?php

namespace Mautic\LeadBundle\Segment\Decorator\Date\Week;

use Mautic\CoreBundle\Helper\DateTimeHelper;

final class DateWeekLast extends DateWeekAbstract
{
    protected function modifyBaseDate(DateTimeHelper $dateTimeHelper): void
    {
        $dateTimeHelper->setDateTime('midnight monday last week', null);
    }
}
