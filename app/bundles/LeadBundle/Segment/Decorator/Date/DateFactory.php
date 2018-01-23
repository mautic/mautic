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

class DateFactory
{
    /**
     * @param string $originalValue
     * @param string $timeframe
     * @param bool   $requiresBetween
     * @param bool   $includeMidnigh
     * @param bool   $isTimestamp
     *
     * @return DateOptionsInterface
     */
    public function getDate($originalValue, $timeframe, $requiresBetween, $includeMidnigh, $isTimestamp)
    {
        $dtHelper = new DateTimeHelper('midnight today', null, 'local');

        switch ($timeframe) {
            case 'birthday':
            case 'anniversary':
                return new DateAnniversary();
            case 'today':
                return new DateDayToday($dtHelper, $requiresBetween, $includeMidnigh, $isTimestamp);
            case 'tomorrow':
                return new DateDayTomorrow($dtHelper, $requiresBetween, $includeMidnigh, $isTimestamp);
            case 'yesterday':
                return new DateDayYesterday($dtHelper, $requiresBetween, $includeMidnigh, $isTimestamp);
            case 'week_last':
                return new DateWeekLast($dtHelper, $requiresBetween, $includeMidnigh, $isTimestamp);
            case 'week_next':
                return new DateWeekNext($dtHelper, $requiresBetween, $includeMidnigh, $isTimestamp);
            case 'week_this':
                return new DateWeekThis($dtHelper, $requiresBetween, $includeMidnigh, $isTimestamp);
            case 'month_last':
                return new DateMonthLast($dtHelper, $requiresBetween, $includeMidnigh, $isTimestamp);
            case 'month_next':
                return new DateMonthNext($dtHelper, $requiresBetween, $includeMidnigh, $isTimestamp);
            case 'month_this':
                return new DateMonthThis($dtHelper, $requiresBetween, $includeMidnigh, $isTimestamp);
            case 'year_last':
                return new DateYearLast();
            case 'year_next':
                return new DateYearNext();
            case 'year_this':
                return new DateYearThis();
            default:
                return new DateDefault($originalValue, $requiresBetween);
        }
    }
}
