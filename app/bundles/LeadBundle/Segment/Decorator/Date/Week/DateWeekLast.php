<?php

namespace Mautic\LeadBundle\Segment\Decorator\Date\Week;

use Mautic\CoreBundle\Helper\DateTimeHelper;

class DateWeekLast extends DateWeekAbstract
{
    protected function modifyBaseDate(DateTimeHelper $dateTimeHelper)
    {
        $dateTimeHelper->setDateTime('midnight monday last week', null);
    }
}
