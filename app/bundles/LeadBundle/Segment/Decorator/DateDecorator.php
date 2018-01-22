<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Segment\Decorator;

use Mautic\LeadBundle\Segment\Decorator\Date\DateFactory;
use Mautic\LeadBundle\Segment\LeadSegmentFilterCrate;
use Mautic\LeadBundle\Segment\LeadSegmentFilterOperator;
use Mautic\LeadBundle\Segment\RelativeDate;
use Mautic\LeadBundle\Services\LeadSegmentFilterDescriptor;

class DateDecorator extends BaseDecorator
{
    /**
     * @var LeadSegmentFilterDescriptor
     */
    private $leadSegmentFilterDescriptor;

    /**
     * @var RelativeDate
     */
    private $relativeDate;

    /**
     * @var DateFactory
     */
    private $dateFactory;

    public function __construct(
        LeadSegmentFilterOperator $leadSegmentFilterOperator,
        LeadSegmentFilterDescriptor $leadSegmentFilterDescriptor,
        RelativeDate $relativeDate,
        DateFactory $dateFactory
    ) {
        parent::__construct($leadSegmentFilterOperator);
        $this->leadSegmentFilterDescriptor = $leadSegmentFilterDescriptor;
        $this->relativeDate                = $relativeDate;
        $this->dateFactory                 = $dateFactory;
    }

    public function getOperator(LeadSegmentFilterCrate $leadSegmentFilterCrate)
    {
        if ($this->isAnniversary($leadSegmentFilterCrate)) {
            return 'like';
        }

        if ($this->requiresBetween($leadSegmentFilterCrate)) {
            return $leadSegmentFilterCrate->getOperator() === '!=' ? 'notBetween' : 'between';
        }

        return parent::getOperator($leadSegmentFilterCrate);
    }

    public function getParameterValue(LeadSegmentFilterCrate $leadSegmentFilterCrate)
    {
        if ($this->isAnniversary($leadSegmentFilterCrate)) {
            return '%'.date('-m-d');
        }

        $isTimestamp     = $this->isTimestamp($leadSegmentFilterCrate);
        $timeframe       = $this->getTimeFrame($leadSegmentFilterCrate);
        $requiresBetween = $this->requiresBetween($leadSegmentFilterCrate);
        $includeMidnigh  = $this->shouldIncludeMidnight($leadSegmentFilterCrate);

        $date = $this->dateFactory->getDate($timeframe, $requiresBetween, $includeMidnigh, $isTimestamp);

        return $date->getDateValue();
    }

    private function isAnniversary(LeadSegmentFilterCrate $leadSegmentFilterCrate)
    {
        $timeframe = $this->getTimeFrame($leadSegmentFilterCrate);

        return $timeframe === 'anniversary' || $timeframe === 'birthday';
    }

    private function requiresBetween(LeadSegmentFilterCrate $leadSegmentFilterCrate)
    {
        return in_array($leadSegmentFilterCrate->getOperator(), ['=', '!='], true);
    }

    private function shouldIncludeMidnight(LeadSegmentFilterCrate $leadSegmentFilterCrate)
    {
        return in_array($this->getOperator($leadSegmentFilterCrate), ['gt', 'lte'], true);
    }

    private function isTimestamp(LeadSegmentFilterCrate $leadSegmentFilterCrate)
    {
        return $leadSegmentFilterCrate->getType() === 'datetime';
    }

    /**
     * @param LeadSegmentFilterCrate $leadSegmentFilterCrate
     *
     * @return string
     */
    private function getTimeFrame(LeadSegmentFilterCrate $leadSegmentFilterCrate)
    {
        $relativeDateStrings = $this->relativeDate->getRelativeDateStrings();
        $key                 = array_search($leadSegmentFilterCrate->getFilter(), $relativeDateStrings, true);

        return str_replace('mautic.lead.list.', '', $key);
    }

    public function getField(LeadSegmentFilterCrate $leadSegmentFilterCrate)
    {
        $originalField = $leadSegmentFilterCrate->getField();

        if (empty($this->leadSegmentFilterDescriptor[$originalField]['field'])) {
            return parent::getField($leadSegmentFilterCrate);
        }

        return $this->leadSegmentFilterDescriptor[$originalField]['field'];
    }

    public function getTable(LeadSegmentFilterCrate $leadSegmentFilterCrate)
    {
        $originalField = $leadSegmentFilterCrate->getField();

        if (empty($this->leadSegmentFilterDescriptor[$originalField]['foreign_table'])) {
            return parent::getTable($leadSegmentFilterCrate);
        }

        return $this->leadSegmentFilterDescriptor[$originalField]['foreign_table'];
    }

    public function getQueryType(LeadSegmentFilterCrate $leadSegmentFilterCrate)
    {
        $originalField = $leadSegmentFilterCrate->getField();

        if (!isset($this->leadSegmentFilterDescriptor[$originalField]['type'])) {
            return parent::getQueryType($leadSegmentFilterCrate);
        }

        return $this->leadSegmentFilterDescriptor[$originalField]['type'];
    }

    public function getAggregateFunc(LeadSegmentFilterCrate $leadSegmentFilterCrate)
    {
        $originalField = $leadSegmentFilterCrate->getField();

        return isset($this->leadSegmentFilterDescriptor[$originalField]['func']) ?
            $this->leadSegmentFilterDescriptor[$originalField]['func'] : false;
    }
}
