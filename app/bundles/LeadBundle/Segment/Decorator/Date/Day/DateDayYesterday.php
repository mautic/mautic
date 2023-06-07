<?php

namespace Mautic\LeadBundle\Segment\Decorator\Date\Day;

use Mautic\CoreBundle\Helper\DateTimeHelper;

class DateDayYesterday extends DateDayAbstract
{
    public const MIDNIGHT_YESTERDAY = 'midnight yesterday';

    /**
     * {@inheritdoc}
     */
    protected function modifyBaseDate(DateTimeHelper $dateTimeHelper)
    {
        $dateTimeHelper->setDateTime(self::MIDNIGHT_YESTERDAY, null);
    }
}
