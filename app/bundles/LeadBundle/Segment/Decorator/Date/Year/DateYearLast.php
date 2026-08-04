<?php

namespace Mautic\LeadBundle\Segment\Decorator\Date\Year;

use Mautic\CoreBundle\Helper\DateTimeHelper;

final class DateYearLast extends DateYearAbstract
{
    protected function modifyBaseDate(DateTimeHelper $dateTimeHelper): void
    {
        $dateTimeHelper->setDateTime('midnight first day of January last year', null);
    }
}
