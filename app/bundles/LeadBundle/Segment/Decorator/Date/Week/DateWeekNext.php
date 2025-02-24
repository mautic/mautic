<?php

namespace Mautic\LeadBundle\Segment\Decorator\Date\Week;

use Mautic\CoreBundle\Helper\DateTimeHelper;

class DateWeekNext extends DateWeekAbstract
{
    protected function modifyBaseDate(DateTimeHelper $dateTimeHelper)
    {
        $dateTimeHelper->setDateTime('midnight monday next week', null);
    }
}
