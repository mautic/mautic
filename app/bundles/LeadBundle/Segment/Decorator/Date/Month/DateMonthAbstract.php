<?php

namespace Mautic\LeadBundle\Segment\Decorator\Date\Month;

use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\LeadBundle\Segment\ContactSegmentFilterCrate;
use Mautic\LeadBundle\Segment\Decorator\Date\DateOptionAbstract;

abstract class DateMonthAbstract extends DateOptionAbstract
{
    /**
     * @return string
     */
    protected function getModifierForBetweenRange()
    {
        return '+1 month';
    }

    /**
     * {@inheritdoc}
     */
    protected function getValueForBetweenRange(DateTimeHelper $dateTimeHelper)
    {
        if (!$this->dateOptionParameters->hasTimePart()) {
            return $dateTimeHelper->getString('Y-m-%');
        }
        $dateFormat = 'Y-m-d H:i:s';
        $startWith  = $dateTimeHelper->toUtcString($dateFormat);

        $modifier = $this->getModifierForBetweenRange().' -1 second';
        $dateTimeHelper->modify($modifier);
        $endWith = $dateTimeHelper->toUtcString($dateFormat);

        return [$startWith, $endWith];
    }

    /**
     * {@inheritdoc}
     */
    protected function getOperatorForBetweenRange(ContactSegmentFilterCrate $leadSegmentFilterCrate)
    {
        if ($this->dateOptionParameters->hasTimePart()) {
            return '!=' === $leadSegmentFilterCrate->getOperator() ? 'notBetween' : 'between';
        }

        return '!=' === $leadSegmentFilterCrate->getOperator() ? 'notLike' : 'like';
    }
}
