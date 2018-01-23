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

use Mautic\LeadBundle\Segment\LeadSegmentFilterCrate;

class DateOptionParameters
{
    /**
     * @var bool
     */
    private $hasTimePart;

    /**
     * @var string
     */
    private $timeframe;

    /**
     * @var bool
     */
    private $requiresBetween;

    /**
     * @var bool
     */
    private $includeMidnigh;

    /**
     * @param LeadSegmentFilterCrate $leadSegmentFilterCrate
     * @param array                  $relativeDateStrings
     */
    public function __construct(LeadSegmentFilterCrate $leadSegmentFilterCrate, array $relativeDateStrings)
    {
        $this->hasTimePart     = $leadSegmentFilterCrate->hasTimeParts();
        $this->timeframe       = $this->parseTimeFrame($leadSegmentFilterCrate, $relativeDateStrings);
        $this->requiresBetween = in_array($leadSegmentFilterCrate->getOperator(), ['=', '!='], true);
        $this->includeMidnigh  = in_array($leadSegmentFilterCrate->getOperator(), ['gt', 'lte'], true);
    }

    /**
     * @return bool
     */
    public function hasTimePart()
    {
        return $this->hasTimePart;
    }

    /**
     * @return string
     */
    public function getTimeframe()
    {
        return $this->timeframe;
    }

    /**
     * @return bool
     */
    public function isBetweenRequired()
    {
        return $this->requiresBetween;
    }

    /**
     * @return bool
     */
    public function shouldIncludeMidnigh()
    {
        return $this->includeMidnigh;
    }

    /**
     * @param LeadSegmentFilterCrate $leadSegmentFilterCrate
     * @param array                  $relativeDateStrings
     *
     * @return string
     */
    private function parseTimeFrame(LeadSegmentFilterCrate $leadSegmentFilterCrate, array $relativeDateStrings)
    {
        $key = array_search($leadSegmentFilterCrate->getFilter(), $relativeDateStrings, true);

        return str_replace('mautic.lead.list.', '', $key);
    }
}
