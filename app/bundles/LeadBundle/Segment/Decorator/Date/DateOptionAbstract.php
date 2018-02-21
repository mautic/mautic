<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Segment\Decorator\Date;

use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\LeadBundle\Segment\ContactSegmentFilterCrate;
use Mautic\LeadBundle\Segment\Decorator\DateDecorator;
use Mautic\LeadBundle\Segment\Decorator\FilterDecoratorInterface;

abstract class DateOptionAbstract implements FilterDecoratorInterface
{
    /**
     * @var DateDecorator
     */
    protected $dateDecorator;

    /**
     * @var DateTimeHelper
     */
    protected $dateTimeHelper;

    /**
     * @var DateOptionParameters
     */
    protected $dateOptionParameters;

    /**
     * @param DateDecorator        $dateDecorator
     * @param DateTimeHelper       $dateTimeHelper
     * @param DateOptionParameters $dateOptionParameters
     */
    public function __construct(DateDecorator $dateDecorator, DateTimeHelper $dateTimeHelper, DateOptionParameters $dateOptionParameters)
    {
        $this->dateDecorator        = $dateDecorator;
        $this->dateTimeHelper       = $dateTimeHelper;
        $this->dateOptionParameters = $dateOptionParameters;
    }

    /**
     * This function is responsible for setting date. $this->dateTimeHelper holds date with midnight today.
     * Eg. +1 day for "tomorrow", -1 for yesterday etc.
     */
    abstract protected function modifyBaseDate();

    /**
     * This function is responsible for date modification for between operator.
     * Eg. +1 day for "today", "tomorrow" and "yesterday", +1 week for "this week", "last week", "next week" etc.
     *
     * @return string
     */
    abstract protected function getModifierForBetweenRange();

    /**
     * This function returns a value if between range is needed. Could return string for like operator or array for between operator
     * Eg. //LIKE 2018-01-23% for today, //LIKE 2017-12-% for last month, //LIKE 2017-% for last year, array for this week.
     *
     * @return string|array
     */
    abstract protected function getValueForBetweenRange();

    /**
     * This function returns an operator if between range is needed. Could return like or between.
     *
     * @param ContactSegmentFilterCrate $leadSegmentFilterCrate
     *
     * @return string
     */
    abstract protected function getOperatorForBetweenRange(ContactSegmentFilterCrate $leadSegmentFilterCrate);

    public function getField(ContactSegmentFilterCrate $contactSegmentFilterCrate)
    {
        return $this->dateDecorator->getField($contactSegmentFilterCrate);
    }

    public function getTable(ContactSegmentFilterCrate $contactSegmentFilterCrate)
    {
        return $this->dateDecorator->getTable($contactSegmentFilterCrate);
    }

    public function getOperator(ContactSegmentFilterCrate $contactSegmentFilterCrate)
    {
        if ($this->dateOptionParameters->isBetweenRequired()) {
            return $this->getOperatorForBetweenRange($contactSegmentFilterCrate);
        }

        return $this->dateDecorator->getOperator($contactSegmentFilterCrate);
    }

    public function getParameterHolder(ContactSegmentFilterCrate $contactSegmentFilterCrate, $argument)
    {
        return $this->dateDecorator->getParameterHolder($contactSegmentFilterCrate, $argument);
    }

    public function getParameterValue(ContactSegmentFilterCrate $contactSegmentFilterCrate)
    {
        $this->modifyBaseDate();

        $modifier   = $this->getModifierForBetweenRange();
        $dateFormat = $this->dateOptionParameters->hasTimePart() ? 'Y-m-d H:i:s' : 'Y-m-d';

        if ($this->dateOptionParameters->isBetweenRequired()) {
            return $this->getValueForBetweenRange();
        }

        if ($this->dateOptionParameters->shouldIncludeMidnigh()) {
            $modifier .= ' -1 second';
            $this->dateTimeHelper->modify($modifier);
        }

        return $this->dateTimeHelper->toUtcString($dateFormat);
    }

    public function getQueryType(ContactSegmentFilterCrate $contactSegmentFilterCrate)
    {
        return $this->dateDecorator->getQueryType($contactSegmentFilterCrate);
    }

    public function getAggregateFunc(ContactSegmentFilterCrate $contactSegmentFilterCrate)
    {
        return $this->dateDecorator->getAggregateFunc($contactSegmentFilterCrate);
    }

    public function getWhere(ContactSegmentFilterCrate $contactSegmentFilterCrate)
    {
        return $this->dateDecorator->getWhere($contactSegmentFilterCrate);
    }
}
