<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Segment\Decorator\Date\Day;

use Mautic\CoreBundle\Helper\DateTimeHelper;

final class DateDayYesterday extends DateDayAbstract
{
    protected function modifyBaseDate(DateTimeHelper $dateTimeHelper): void
    {
        $dateTimeHelper->modify('midnight yesterday');
    }
}
