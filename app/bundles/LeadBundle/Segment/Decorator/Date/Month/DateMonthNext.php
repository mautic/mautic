<?php

namespace Mautic\LeadBundle\Segment\Decorator\Date\Month;

use Mautic\CoreBundle\Helper\DateTimeHelper;

class DateMonthNext extends DateMonthAbstract
{
    protected function modifyBaseDate(DateTimeHelper $dateTimeHelper)
    {
        $dateTimeHelper->setDateTime('midnight first day of next month', null);
    }
}
