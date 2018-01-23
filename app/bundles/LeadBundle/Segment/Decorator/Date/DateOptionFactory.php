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

class DateOptionFactory
{
    /**
     * @var DateDecorator
     */
    private $dateDecorator;

    public function __construct(DateDecorator $dateDecorator)
    {
        $this->dateDecorator = $dateDecorator;
    }

    /**
     * @param string $originalValue
     * @param string $timeframe
     * @param bool   $requiresBetween
     * @param bool   $includeMidnigh
     * @param bool   $isTimestamp
     *
     * @return FilterDecoratorInterface
     */
    public function getDate($originalValue, $timeframe, $requiresBetween, $includeMidnigh, $isTimestamp)
    {
        $dtHelper = new DateTimeHelper('midnight today', null, 'local');

        switch ($timeframe) {
            case 'birthday':
            case 'anniversary':
                return new DateAnniversary($this->dateDecorator);
            case 'today':
                return new DateDayToday($this->dateDecorator, $dtHelper, $requiresBetween, $includeMidnigh, $isTimestamp);
            case 'tomorrow':
                return new DateDayTomorrow($this->dateDecorator, $dtHelper, $requiresBetween, $includeMidnigh, $isTimestamp);
            case 'yesterday':
                return new DateDayYesterday($this->dateDecorator, $dtHelper, $requiresBetween, $includeMidnigh, $isTimestamp);
            case 'week_last':
                return new DateWeekLast($this->dateDecorator, $dtHelper, $requiresBetween, $includeMidnigh, $isTimestamp);
            case 'week_next':
                return new DateWeekNext($this->dateDecorator, $dtHelper, $requiresBetween, $includeMidnigh, $isTimestamp);
            case 'week_this':
                return new DateWeekThis($this->dateDecorator, $dtHelper, $requiresBetween, $includeMidnigh, $isTimestamp);
            case 'month_last':
                return new DateMonthLast($this->dateDecorator, $dtHelper, $requiresBetween, $includeMidnigh, $isTimestamp);
            case 'month_next':
                return new DateMonthNext($this->dateDecorator, $dtHelper, $requiresBetween, $includeMidnigh, $isTimestamp);
            case 'month_this':
                return new DateMonthThis($this->dateDecorator, $dtHelper, $requiresBetween, $includeMidnigh, $isTimestamp);
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
