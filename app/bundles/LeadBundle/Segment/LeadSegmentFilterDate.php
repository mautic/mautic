<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Segment;

use Mautic\CoreBundle\Helper\DateTimeHelper;

class LeadSegmentFilterDate
{
    /**
     * @var RelativeDate
     */
    private $relativeDate;

    public function __construct(RelativeDate $relativeDate)
    {
        $this->relativeDate = $relativeDate;
    }

    public function fixDateOptions(LeadSegmentFilter $leadSegmentFilter)
    {
        $type = $leadSegmentFilter->getType();
        if ($type !== 'datetime' && $type !== 'date') {
            return;
        }

        if (is_array($leadSegmentFilter->getFilter())) {
            foreach ($leadSegmentFilter->getFilter() as $filterValue) {
                $this->getDate($filterValue, $leadSegmentFilter);
            }
        } else {
            $this->getDate($leadSegmentFilter->getFilter(), $leadSegmentFilter);
        }
    }

    private function getDate($string, LeadSegmentFilter $leadSegmentFilter)
    {
        $relativeDateStrings = $this->relativeDate->getRelativeDateStrings();

        // Check if the column type is a date/time stamp
        $isTimestamp = $leadSegmentFilter->getType() === 'datetime';

        $key             = array_search($string, $relativeDateStrings, true);
        $dtHelper        = new DateTimeHelper('midnight today', null, 'local');
        $requiresBetween = in_array($leadSegmentFilter->getFunc(), ['eq', 'neq'], true) && $isTimestamp;
        $timeframe       = str_replace('mautic.lead.list.', '', $key);
        $modifier        = false;
        $isRelative      = true;

        switch ($timeframe) {
            case 'birthday':
            case 'anniversary':
                $isRelative          = false;
                $leadSegmentFilter->setOperator('like');
                $leadSegmentFilter->setFilter('%'.date('-m-d'));
                break;
            case 'today':
            case 'tomorrow':
            case 'yesterday':
                if ($timeframe === 'yesterday') {
                    $dtHelper->modify('-1 day');
                } elseif ($timeframe === 'tomorrow') {
                    $dtHelper->modify('+1 day');
                }

                // Today = 2015-08-28 00:00:00
                if ($requiresBetween) {
                    // eq:
                    //  field >= 2015-08-28 00:00:00
                    //  field < 2015-08-29 00:00:00

                    // neq:
                    // field < 2015-08-28 00:00:00
                    // field >= 2015-08-29 00:00:00
                    $modifier = '+1 day';
                } else {
                    // lt:
                    //  field < 2015-08-28 00:00:00
                    // gt:
                    //  field > 2015-08-28 23:59:59

                    // lte:
                    //  field <= 2015-08-28 23:59:59
                    // gte:
                    //  field >= 2015-08-28 00:00:00
                    if (in_array($leadSegmentFilter->getFunc(), ['gt', 'lte'], true)) {
                        $modifier = '+1 day -1 second';
                    }
                }
                break;
            case 'week_last':
            case 'week_next':
            case 'week_this':
                $interval = str_replace('week_', '', $timeframe);
                $dtHelper->setDateTime('midnight monday '.$interval.' week', null);

                // This week: Monday 2015-08-24 00:00:00
                if ($requiresBetween) {
                    // eq:
                    //  field >= Mon 2015-08-24 00:00:00
                    //  field <  Mon 2015-08-31 00:00:00

                    // neq:
                    // field <  Mon 2015-08-24 00:00:00
                    // field >= Mon 2015-08-31 00:00:00
                    $modifier = '+1 week';
                } else {
                    // lt:
                    //  field < Mon 2015-08-24 00:00:00
                    // gt:
                    //  field > Sun 2015-08-30 23:59:59

                    // lte:
                    //  field <= Sun 2015-08-30 23:59:59
                    // gte:
                    //  field >= Mon 2015-08-24 00:00:00
                    if (in_array($leadSegmentFilter->getFunc(), ['gt', 'lte'], true)) {
                        $modifier = '+1 week -1 second';
                    }
                }
                break;

            case 'month_last':
            case 'month_next':
            case 'month_this':
                $interval = substr($key, -4);
                $dtHelper->setDateTime('midnight first day of '.$interval.' month', null);

                // This month: 2015-08-01 00:00:00
                if ($requiresBetween) {
                    // eq:
                    //  field >= 2015-08-01 00:00:00
                    //  field <  2015-09:01 00:00:00

                    // neq:
                    // field <  2015-08-01 00:00:00
                    // field >= 2016-09-01 00:00:00
                    $modifier = '+1 month';
                } else {
                    // lt:
                    //  field < 2015-08-01 00:00:00
                    // gt:
                    //  field > 2015-08-31 23:59:59

                    // lte:
                    //  field <= 2015-08-31 23:59:59
                    // gte:
                    //  field >= 2015-08-01 00:00:00
                    if (in_array($leadSegmentFilter->getFunc(), ['gt', 'lte'], true)) {
                        $modifier = '+1 month -1 second';
                    }
                }
                break;
            case 'year_last':
            case 'year_next':
            case 'year_this':
                $interval = substr($key, -4);
                $dtHelper->setDateTime('midnight first day of '.$interval.' year', null);

                // This year: 2015-01-01 00:00:00
                if ($requiresBetween) {
                    // eq:
                    //  field >= 2015-01-01 00:00:00
                    //  field <  2016-01-01 00:00:00

                    // neq:
                    // field <  2015-01-01 00:00:00
                    // field >= 2016-01-01 00:00:00
                    $modifier = '+1 year';
                } else {
                    // lt:
                    //  field < 2015-01-01 00:00:00
                    // gt:
                    //  field > 2015-12-31 23:59:59

                    // lte:
                    //  field <= 2015-12-31 23:59:59
                    // gte:
                    //  field >= 2015-01-01 00:00:00
                    if (in_array($leadSegmentFilter->getFunc(), ['gt', 'lte'], true)) {
                        $modifier = '+1 year -1 second';
                    }
                }
                break;
            default:
                $isRelative = false;
                break;
        }

        // check does this match php date params pattern?
        if ($timeframe !== 'anniversary' &&
            (stristr($string[0], '-') || stristr($string[0], '+'))) {
            $date = new \DateTime('now');
            $date->modify($string);

            $dateTime = $date->format('Y-m-d H:i:s');
            $dtHelper->setDateTime($dateTime, null);

            $isRelative = true;
        }

        if ($isRelative) {
            if ($requiresBetween) {
                $startWith = $isTimestamp ? $dtHelper->toUtcString('Y-m-d H:i:s') : $dtHelper->toUtcString('Y-m-d');

                $dtHelper->modify($modifier);
                $endWith = $isTimestamp ? $dtHelper->toUtcString('Y-m-d H:i:s') : $dtHelper->toUtcString('Y-m-d');

                // Use a between statement
                $func = ($leadSegmentFilter->getFunc() === 'neq') ? 'notBetween' : 'between';
                $leadSegmentFilter->setFunc($func);

                $leadSegmentFilter->setFilter([$startWith, $endWith]);
            } else {
                if ($modifier) {
                    $dtHelper->modify($modifier);
                }

                $filter = $isTimestamp ? $dtHelper->toUtcString('Y-m-d H:i:s') : $dtHelper->toUtcString('Y-m-d');
                $leadSegmentFilter->setFilter($filter);
            }
        }
    }
}
