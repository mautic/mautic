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

use Mautic\LeadBundle\Segment\Decorator\FilterDecoratorInterface;
use Mautic\LeadBundle\Segment\LeadSegmentFilterCrate;
use Mautic\LeadBundle\Segment\RelativeDate;

class DateFactory
{
    /**
     * @var DateOptionFactory
     */
    private $dateOptionFactory;

    /**
     * @var RelativeDate
     */
    private $relativeDate;

    public function __construct(
        DateOptionFactory $dateOptionFactory,
        RelativeDate $relativeDate
    ) {
        $this->dateOptionFactory = $dateOptionFactory;
        $this->relativeDate      = $relativeDate;
    }

    /**
     * @param LeadSegmentFilterCrate $leadSegmentFilterCrate
     *
     * @return FilterDecoratorInterface
     */
    public function getDateOption(LeadSegmentFilterCrate $leadSegmentFilterCrate)
    {
        $originalValue   = $leadSegmentFilterCrate->getFilter();
        $isTimestamp     = $this->isTimestamp($leadSegmentFilterCrate);
        $timeframe       = $this->getTimeFrame($leadSegmentFilterCrate);
        $requiresBetween = $this->requiresBetween($leadSegmentFilterCrate);
        $includeMidnigh  = $this->shouldIncludeMidnight($leadSegmentFilterCrate);

        return $this->dateOptionFactory->getDate($originalValue, $timeframe, $requiresBetween, $includeMidnigh, $isTimestamp);
    }

    private function requiresBetween(LeadSegmentFilterCrate $leadSegmentFilterCrate)
    {
        return in_array($leadSegmentFilterCrate->getOperator(), ['=', '!='], true);
    }

    private function shouldIncludeMidnight(LeadSegmentFilterCrate $leadSegmentFilterCrate)
    {
        return in_array($leadSegmentFilterCrate->getOperator(), ['gt', 'lte'], true);
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
}
