<?php

namespace Mautic\LeadBundle\Segment\Decorator\Date\Week;

use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\LeadBundle\Segment\ContactSegmentFilterCrate;
use Mautic\LeadBundle\Segment\Decorator\Date\DateOptionAbstract;

abstract class DateWeekAbstract extends DateOptionAbstract
{
    /**
     * @return string
     */
    protected function getModifierForBetweenRange()
    {
        return '+1 week';
    }

    protected function getValueForBetweenRange(DateTimeHelper $dateTimeHelper)
    {
        $dateFormat = $this->dateOptionParameters->hasTimePart() ? 'Y-m-d H:i:s' : 'Y-m-d';
        $startWith  = $dateTimeHelper->toLocalString($dateFormat);

        $modifier = $this->getModifierForBetweenRange().' -1 second';
        $dateTimeHelper->modify($modifier);
        $endWith = $dateTimeHelper->toLocalString($dateFormat);

        return [$startWith, $endWith];
    }

    protected function getOperatorForBetweenRange(ContactSegmentFilterCrate $leadSegmentFilterCrate)
    {
        return '!=' === $leadSegmentFilterCrate->getOperator() ? 'notBetween' : 'between';
    }
}
