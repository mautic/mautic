<?php

namespace Mautic\LeadBundle\Segment\Decorator\Date\Day;

use Mautic\CoreBundle\Helper\DateTimeHelper;

class DateDayTomorrow extends DateDayAbstract
{
    protected function modifyBaseDate(DateTimeHelper $dateTimeHelper)
    {
        $dateTimeHelper->modify('midnight tomorrow');
    }
}
