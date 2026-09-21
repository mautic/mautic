<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Segment\Decorator\Date\Week;

use Mautic\CoreBundle\Helper\DateTimeHelper;

final class DateWeekThis extends DateWeekAbstract
{
    protected function modifyBaseDate(DateTimeHelper $dateTimeHelper): void
    {
        $dateTimeHelper->setDateTime('midnight monday this week', null);
    }
}
