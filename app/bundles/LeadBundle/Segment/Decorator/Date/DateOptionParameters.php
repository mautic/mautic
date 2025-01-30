<?php

namespace Mautic\LeadBundle\Segment\Decorator\Date;

use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\LeadBundle\Segment\ContactSegmentFilterCrate;

class DateOptionParameters
{
    private bool $hasTimePart;

    /**
     * @var mixed
     */
    private $timeframe;

    private bool $requiresBetween;

    private bool $shouldUseLastDayOfRange;

    private DateTimeHelper $dateTimeHelper;

    public function __construct(
        ContactSegmentFilterCrate $leadSegmentFilterCrate,
        array $relativeDateStrings,
        TimezoneResolver $timezoneResolver,
    ) {
        $this->hasTimePart             = $leadSegmentFilterCrate->hasTimeParts();
        $this->timeframe               = $this->parseTimeFrame($leadSegmentFilterCrate, $relativeDateStrings);
        $this->requiresBetween         = in_array($leadSegmentFilterCrate->getOperator(), ['=', '!='], true);
        $this->shouldUseLastDayOfRange = in_array($leadSegmentFilterCrate->getOperator(), ['gt', 'lte'], true);

        $this->setDateTimeHelper($timezoneResolver);
    }

    public function hasTimePart(): bool
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

    public function isBetweenRequired(): bool
    {
        return $this->requiresBetween;
    }

    /**
     * This function indicates that we need to modify date to the last date of range.
     * "Less than or equal" operator means that we need to include whole week / month / year > last day from range
     * "Grater than" needs same logic.
     */
    public function shouldUseLastDayOfRange(): bool
    {
        return $this->shouldUseLastDayOfRange;
    }

    /**
     * @return DateTimeHelper
     */
    public function getDefaultDate()
    {
        return $this->dateTimeHelper;
    }

    /**
     * @return string|mixed
     */
    private function parseTimeFrame(ContactSegmentFilterCrate $leadSegmentFilterCrate, array $relativeDateStrings)
    {
        $key = array_search($leadSegmentFilterCrate->getFilter(), $relativeDateStrings, true);

        if (false === $key) {
            // Time frame does not match any option from $relativeDateStrings, so return original value
            return $leadSegmentFilterCrate->getFilter();
        }

        return str_replace('mautic.lead.list.', '', $key);
    }

    private function setDateTimeHelper(TimezoneResolver $timezoneResolver): void
    {
        $this->dateTimeHelper = $timezoneResolver->getDefaultDate($this->hasTimePart());
    }
}
