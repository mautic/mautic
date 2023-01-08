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

    /**
     * {@inheritdoc}
     */
    protected function getValueForBetweenRange(DateTimeHelper $dateTimeHelper)
    {
        $modifier = $this->getModifierForBetweenRange().' -1 second';

        if (!$this->dateOptionParameters->hasTimePart()) {
            $startWith = $dateTimeHelper->getString('Y-m-d');
            $dateTimeHelper->modify($modifier);
            $endWith= $dateTimeHelper->getString('Y-m-d');

            return [$startWith, $endWith];
        }

        $dateFormat = 'Y-m-d H:i:s';
        $startWith  = $dateTimeHelper->toUtcString($dateFormat);

        $dateTimeHelper->modify($modifier);
        $endWith = $dateTimeHelper->toUtcString($dateFormat);

        return [$startWith, $endWith];
    }

    /**
     * {@inheritdoc}
     */
    protected function getOperatorForBetweenRange(ContactSegmentFilterCrate $leadSegmentFilterCrate)
    {
        return '!=' === $leadSegmentFilterCrate->getOperator() ? 'notBetween' : 'between';
    }
}
