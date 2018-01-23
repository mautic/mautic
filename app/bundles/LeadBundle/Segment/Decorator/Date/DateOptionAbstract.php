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
use Mautic\LeadBundle\Segment\Decorator\DateDecorator;
use Mautic\LeadBundle\Segment\Decorator\FilterDecoratorInterface;
use Mautic\LeadBundle\Segment\LeadSegmentFilterCrate;

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
     * @var bool
     */
    private $requiresBetween;

    /**
     * @var bool
     */
    private $includeMidnigh;

    /**
     * @var bool
     */
    protected $isTimestamp;

    /**
     * @param DateDecorator  $dateDecorator
     * @param DateTimeHelper $dateTimeHelper
     * @param bool           $requiresBetween
     * @param bool           $includeMidnigh
     * @param bool           $isTimestamp
     */
    public function __construct(DateDecorator $dateDecorator, DateTimeHelper $dateTimeHelper, $requiresBetween, $includeMidnigh, $isTimestamp)
    {
        $this->dateDecorator   = $dateDecorator;
        $this->dateTimeHelper  = $dateTimeHelper;
        $this->requiresBetween = $requiresBetween;
        $this->includeMidnigh  = $includeMidnigh;
        $this->isTimestamp     = $isTimestamp;
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
     * @param LeadSegmentFilterCrate $leadSegmentFilterCrate
     *
     * @return string
     */
    abstract protected function getOperatorForBetweenRange(LeadSegmentFilterCrate $leadSegmentFilterCrate);

    public function getField(LeadSegmentFilterCrate $leadSegmentFilterCrate)
    {
        return $this->dateDecorator->getField($leadSegmentFilterCrate);
    }

    public function getTable(LeadSegmentFilterCrate $leadSegmentFilterCrate)
    {
        return $this->dateDecorator->getTable($leadSegmentFilterCrate);
    }

    public function getOperator(LeadSegmentFilterCrate $leadSegmentFilterCrate)
    {
        if ($this->requiresBetween) {
            return $this->getOperatorForBetweenRange($leadSegmentFilterCrate);
        }

        return $this->dateDecorator->getOperator($leadSegmentFilterCrate);
    }

    public function getParameterHolder(LeadSegmentFilterCrate $leadSegmentFilterCrate, $argument)
    {
        return $this->dateDecorator->getParameterHolder($leadSegmentFilterCrate, $argument);
    }

    public function getParameterValue(LeadSegmentFilterCrate $leadSegmentFilterCrate)
    {
        $this->modifyBaseDate();

        $modifier   = $this->getModifierForBetweenRange();
        $dateFormat = $this->isTimestamp ? 'Y-m-d H:i:s' : 'Y-m-d';

        if ($this->requiresBetween) {
            return $this->getValueForBetweenRange();
        }

        if ($this->includeMidnigh) {
            $modifier .= ' -1 second';
            $this->dateTimeHelper->modify($modifier);
        }

        return $this->dateTimeHelper->toUtcString($dateFormat);
    }

    public function getQueryType(LeadSegmentFilterCrate $leadSegmentFilterCrate)
    {
        return $this->dateDecorator->getQueryType($leadSegmentFilterCrate);
    }

    public function getAggregateFunc(LeadSegmentFilterCrate $leadSegmentFilterCrate)
    {
        return $this->dateDecorator->getAggregateFunc($leadSegmentFilterCrate);
    }
}
