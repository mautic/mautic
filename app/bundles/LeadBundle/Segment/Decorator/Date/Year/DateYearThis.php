<?php

namespace Mautic\LeadBundle\Segment\Decorator\Date\Year;

use Mautic\CoreBundle\Helper\DateTimeHelper;

final class DateYearThis extends DateYearAbstract
{
    protected function modifyBaseDate(DateTimeHelper $dateTimeHelper): void
    {
        $dateTimeHelper->setDateTime('midnight first day of January this year', null);
    }
}
