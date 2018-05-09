<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
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
     * @var DateOptionParameters
     */
    protected $dateOptionParameters;

    /**
     * @param DateDecorator        $dateDecorator
     * @param DateOptionParameters $dateOptionParameters
     */
    public function __construct(DateDecorator $dateDecorator, DateOptionParameters $dateOptionParameters)
    {
        $this->dateDecorator        = $dateDecorator;
        $this->dateOptionParameters = $dateOptionParameters;
    }

    /**
     * This function is responsible for setting date. $this->dateTimeHelper holds date with midnight today.
     * Eg. +1 day for "tomorrow", -1 for yesterday etc.
     *
     * @param DateTimeHelper $dateTimeHelper
     */
    abstract protected function modifyBaseDate(DateTimeHelper $dateTimeHelper);

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
     * @param DateTimeHelper $dateTimeHelper
     *
     * @return string|array
     */
    abstract protected function getValueForBetweenRange(DateTimeHelper $dateTimeHelper);

    /**
     * This function returns an operator if between range is needed. Could return like or between.
     *
     * @param ContactSegmentFilterCrate $leadSegmentFilterCrate
     *
     * @return string
     */
    abstract protected function getOperatorForBetweenRange(ContactSegmentFilterCrate $leadSegmentFilterCrate);

    /**
     * @param ContactSegmentFilterCrate $contactSegmentFilterCrate
     *
     * @return null|string
     */
    public function getField(ContactSegmentFilterCrate $contactSegmentFilterCrate)
    {
        return $this->dateDecorator->getField($contactSegmentFilterCrate);
    }

    /**
     * @param ContactSegmentFilterCrate $contactSegmentFilterCrate
     *
     * @return string
     */
    public function getTable(ContactSegmentFilterCrate $contactSegmentFilterCrate)
    {
        return $this->dateDecorator->getTable($contactSegmentFilterCrate);
    }

    /**
     * @param ContactSegmentFilterCrate $contactSegmentFilterCrate
     *
     * @return string
     */
    public function getOperator(ContactSegmentFilterCrate $contactSegmentFilterCrate)
    {
        if ($this->dateOptionParameters->isBetweenRequired()) {
            return $this->getOperatorForBetweenRange($contactSegmentFilterCrate);
        }

        return $this->dateDecorator->getOperator($contactSegmentFilterCrate);
    }

    /**
     * @param ContactSegmentFilterCrate $contactSegmentFilterCrate
     * @param array|string              $argument
     *
     * @return array|string
     */
    public function getParameterHolder(ContactSegmentFilterCrate $contactSegmentFilterCrate, $argument)
    {
        return $this->dateDecorator->getParameterHolder($contactSegmentFilterCrate, $argument);
    }

    /**
     * @param ContactSegmentFilterCrate $contactSegmentFilterCrate
     *
     * @return array|bool|float|null|string
     */
    public function getParameterValue(ContactSegmentFilterCrate $contactSegmentFilterCrate)
    {
        $dateTimeHelper = $this->dateDecorator->getDefaultDate();

        $this->modifyBaseDate($dateTimeHelper);

        $dateFormat = $this->dateOptionParameters->hasTimePart() ? 'Y-m-d H:i:s' : 'Y-m-d';

        if ($this->dateOptionParameters->isBetweenRequired()) {
            return $this->getValueForBetweenRange($dateTimeHelper);
        }

        if ($this->dateOptionParameters->shouldUseLastDayOfRange()) {
            $modifier = $this->getModifierForBetweenRange();
            $modifier .= ' -1 second';
            $dateTimeHelper->modify($modifier);
        }

        return $dateTimeHelper->toUtcString($dateFormat);
    }

    /**
     * @param ContactSegmentFilterCrate $contactSegmentFilterCrate
     *
     * @return string
     */
    public function getQueryType(ContactSegmentFilterCrate $contactSegmentFilterCrate)
    {
        return $this->dateDecorator->getQueryType($contactSegmentFilterCrate);
    }

    /**
     * @param ContactSegmentFilterCrate $contactSegmentFilterCrate
     *
     * @return bool|string
     */
    public function getAggregateFunc(ContactSegmentFilterCrate $contactSegmentFilterCrate)
    {
        return $this->dateDecorator->getAggregateFunc($contactSegmentFilterCrate);
    }

    /**
     * @param ContactSegmentFilterCrate $contactSegmentFilterCrate
     *
     * @return \Mautic\LeadBundle\Segment\Query\Expression\CompositeExpression|null|string
     */
    public function getWhere(ContactSegmentFilterCrate $contactSegmentFilterCrate)
    {
        return $this->dateDecorator->getWhere($contactSegmentFilterCrate);
    }
}
