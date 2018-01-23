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
use Mautic\LeadBundle\Segment\Decorator\Date\Day\DateDayToday;
use Mautic\LeadBundle\Segment\Decorator\Date\Day\DateDayTomorrow;
use Mautic\LeadBundle\Segment\Decorator\Date\Day\DateDayYesterday;
use Mautic\LeadBundle\Segment\Decorator\Date\Month\DateMonthLast;
use Mautic\LeadBundle\Segment\Decorator\Date\Month\DateMonthNext;
use Mautic\LeadBundle\Segment\Decorator\Date\Month\DateMonthThis;
use Mautic\LeadBundle\Segment\Decorator\Date\Other\DateAnniversary;
use Mautic\LeadBundle\Segment\Decorator\Date\Other\DateDefault;
use Mautic\LeadBundle\Segment\Decorator\Date\Week\DateWeekLast;
use Mautic\LeadBundle\Segment\Decorator\Date\Week\DateWeekNext;
use Mautic\LeadBundle\Segment\Decorator\Date\Week\DateWeekThis;
use Mautic\LeadBundle\Segment\Decorator\Date\Year\DateYearLast;
use Mautic\LeadBundle\Segment\Decorator\Date\Year\DateYearNext;
use Mautic\LeadBundle\Segment\Decorator\Date\Year\DateYearThis;
use Mautic\LeadBundle\Segment\Decorator\DateDecorator;
use Mautic\LeadBundle\Segment\Decorator\FilterDecoratorInterface;
use Mautic\LeadBundle\Segment\LeadSegmentFilterCrate;
use Mautic\LeadBundle\Segment\RelativeDate;

class DateOptionFactory
{
    /**
     * @var DateDecorator
     */
    private $dateDecorator;

    /**
     * @var RelativeDate
     */
    private $relativeDate;

    public function __construct(
        DateDecorator $dateDecorator,
        RelativeDate $relativeDate
    ) {
        $this->dateDecorator = $dateDecorator;
        $this->relativeDate  = $relativeDate;
    }

    /**
     * @param LeadSegmentFilterCrate $leadSegmentFilterCrate
     *
     * @return FilterDecoratorInterface
     */
    public function getDateOption(LeadSegmentFilterCrate $leadSegmentFilterCrate)
    {
        $originalValue        = $leadSegmentFilterCrate->getFilter();
        $relativeDateStrings  = $this->relativeDate->getRelativeDateStrings();
        $dateOptionParameters = new DateOptionParameters($leadSegmentFilterCrate, $relativeDateStrings);

        $dtHelper = new DateTimeHelper('midnight today', null, 'local');

        switch ($dateOptionParameters->getTimeframe()) {
            case 'birthday':
            case 'anniversary':
                return new DateAnniversary($this->dateDecorator);
            case 'today':
                return new DateDayToday($this->dateDecorator, $dtHelper, $dateOptionParameters);
            case 'tomorrow':
                return new DateDayTomorrow($this->dateDecorator, $dtHelper, $dateOptionParameters);
            case 'yesterday':
                return new DateDayYesterday($this->dateDecorator, $dtHelper, $dateOptionParameters);
            case 'week_last':
                return new DateWeekLast($this->dateDecorator, $dtHelper, $dateOptionParameters);
            case 'week_next':
                return new DateWeekNext($this->dateDecorator, $dtHelper, $dateOptionParameters);
            case 'week_this':
                return new DateWeekThis($this->dateDecorator, $dtHelper, $dateOptionParameters);
            case 'month_last':
                return new DateMonthLast($this->dateDecorator, $dtHelper, $dateOptionParameters);
            case 'month_next':
                return new DateMonthNext($this->dateDecorator, $dtHelper, $dateOptionParameters);
            case 'month_this':
                return new DateMonthThis($this->dateDecorator, $dtHelper, $dateOptionParameters);
            case 'year_last':
                return new DateYearLast();
            case 'year_next':
                return new DateYearNext();
            case 'year_this':
                return new DateYearThis();
            default:
                return new DateDefault($this->dateDecorator, $originalValue);
        }
    }
}
