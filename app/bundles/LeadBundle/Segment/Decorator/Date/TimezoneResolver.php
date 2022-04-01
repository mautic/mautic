<?php

namespace Mautic\LeadBundle\Segment\Decorator\Date;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\DateTimeHelper;

class TimezoneResolver
{
    /**
     * @var CoreParametersHelper
     */
    private $coreParametersHelper;

    public function __construct(
        CoreParametersHelper $coreParametersHelper
    ) {
        $this->coreParametersHelper = $coreParametersHelper;
    }

    /**
     * @param bool $hasTimePart
     *
     * @return DateTimeHelper
     */
    public function getDefaultDate($hasTimePart)
    {
        /**
         * $hasTimePart tells us if field in a database is date or datetime
         * All datetime fields are stored in UTC
         * Date field, however, is always stored in a local time (there is no time information, so it cannot be converted to UTC).
         *
         * We will generate default date according to this. We need midnight as a default date (for relative intervals like "today" or "-1 day"
         *  1) in UTC for datetime fields
         *  2) in the local timezone for date fields
         *
         * Later we use toLocalString() method - it gives us midnight in UTC for first condition and midnight in local timezone for second option.
         */
        $timezone = $hasTimePart ? 'UTC' : $this->coreParametersHelper->get('default_timezone', 'UTC');

        $date = new \DateTime('midnight today', new \DateTimeZone($timezone));

        return new DateTimeHelper($date, null, $timezone);
    }
}
