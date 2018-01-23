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
                //LIKE 2018-01-23%
                return new DateDayToday($this->dateDecorator, $dtHelper, $requiresBetween, $includeMidnigh, $isTimestamp);
            case 'tomorrow':
                //LIKE 2018-01-24%
                return new DateDayTomorrow($this->dateDecorator, $dtHelper, $requiresBetween, $includeMidnigh, $isTimestamp);
            case 'yesterday':
                //LIKE 2018-01-22%
                return new DateDayYesterday($this->dateDecorator, $dtHelper, $requiresBetween, $includeMidnigh, $isTimestamp);
            case 'week_last':
                return new DateWeekLast($this->dateDecorator, $dtHelper, $requiresBetween, $includeMidnigh, $isTimestamp);
            case 'week_next':
                return new DateWeekNext($this->dateDecorator, $dtHelper, $requiresBetween, $includeMidnigh, $isTimestamp);
            case 'week_this':
                return new DateWeekThis($this->dateDecorator, $dtHelper, $requiresBetween, $includeMidnigh, $isTimestamp);
            case 'month_last':
                //LIKE 2017-12-%
                return new DateMonthLast($this->dateDecorator, $dtHelper, $requiresBetween, $includeMidnigh, $isTimestamp);
            case 'month_next':
                //LIKE 2018-02-%
                return new DateMonthNext($this->dateDecorator, $dtHelper, $requiresBetween, $includeMidnigh, $isTimestamp);
            case 'month_this':
                //LIKE 2018-01-%
                return new DateMonthThis($this->dateDecorator, $dtHelper, $requiresBetween, $includeMidnigh, $isTimestamp);
            case 'year_last':
                //LIKE 2017-%
                return new DateYearLast();
                //LIKE 2019-%
            case 'year_next':
                return new DateYearNext();
            case 'year_this':
                //LIKE 2018-%
                return new DateYearThis();
            default:
                return new DateDefault($this->dateDecorator, $originalValue);
        }
    }
}
