<?php

namespace Mautic\LeadBundle\Segment\Decorator\Date\Year;

use Mautic\CoreBundle\Helper\DateTimeHelper;

final class DateYearNext extends DateYearAbstract
{
    protected function modifyBaseDate(DateTimeHelper $dateTimeHelper): void
    {
        $dateTimeHelper->setDateTime('midnight first day of January next year', null);
    }
}
