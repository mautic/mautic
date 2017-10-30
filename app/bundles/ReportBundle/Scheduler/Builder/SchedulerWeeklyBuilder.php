<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Scheduler\Builder;

use Mautic\ReportBundle\Scheduler\BuilderInterface;
use Mautic\ReportBundle\Scheduler\Enum\SchedulerEnum;
use Mautic\ReportBundle\Scheduler\Exception\InvalidSchedulerException;
use Mautic\ReportBundle\Scheduler\SchedulerInterface;
use Recurr\Exception\InvalidArgument;
use Recurr\Exception\InvalidRRule;
use Recurr\Rule;

class SchedulerWeeklyBuilder implements BuilderInterface
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
            $rule->setFreq('WEEKLY');

            if ($scheduler->isScheduledWeekDays()) {
                $days = SchedulerEnum::getWeekDays();
            } else {
                $days = [$scheduler->getScheduleDay()];
            }

            $rule->setByDay($days);
        } catch (InvalidArgument $e) {
            throw new InvalidSchedulerException();
        } catch (InvalidRRule $e) {
            throw new InvalidSchedulerException();
        }

        return $rule;
    }
}
