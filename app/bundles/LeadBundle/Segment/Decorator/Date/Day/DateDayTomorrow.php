<?php

namespace Mautic\LeadBundle\Segment\Decorator\Date\Day;

use Mautic\CoreBundle\Helper\DateTimeHelper;

class DateDayTomorrow extends DateDayAbstract
{
    public const MIDNIGHT_TOMORROW = 'midnight tomorrow';

    /**
     * {@inheritdoc}
     */
    protected function modifyBaseDate(DateTimeHelper $dateTimeHelper)
    {
        $dateTimeHelper->setDateTime(self::MIDNIGHT_TOMORROW, null);
    }
}
