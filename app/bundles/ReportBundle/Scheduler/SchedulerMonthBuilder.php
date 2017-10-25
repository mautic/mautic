<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Scheduler;

use Mautic\ReportBundle\Exception\InvalidSchedulerException;
use Recurr\Exception\InvalidArgument;
use Recurr\Exception\InvalidRRule;
use Recurr\Rule;

class SchedulerMonthBuilder
{
    /**
     * @param Rule               $rule
     * @param SchedulerInterface $scheduler
     *
     * @return Rule
     *
     * @throws InvalidSchedulerException
     */
    public function build(Rule $rule, SchedulerInterface $scheduler)
    {
        try {
            $frequency   = $scheduler->getScheduleMonthFrequency();
            $scheduleDay = $scheduler->getScheduleDay();
            $day         = $frequency.$scheduleDay;

            $rule->setFreq('MONTHLY');
            $rule->setByDay([$day]);
        } catch (InvalidArgument $e) {
            throw new InvalidSchedulerException();
        } catch (InvalidRRule $e) {
            throw new InvalidSchedulerException();
        }

        return $rule;
    }
}
