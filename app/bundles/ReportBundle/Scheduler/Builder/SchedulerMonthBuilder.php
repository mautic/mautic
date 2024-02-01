<?php

namespace Mautic\ReportBundle\Scheduler\Builder;

use Mautic\ReportBundle\Scheduler\BuilderInterface;
use Mautic\ReportBundle\Scheduler\Enum\SchedulerEnum;
use Mautic\ReportBundle\Scheduler\Exception\InvalidSchedulerException;
use Mautic\ReportBundle\Scheduler\SchedulerInterface;
use Recurr\Exception\InvalidArgument;
use Recurr\Exception\InvalidRRule;
use Recurr\Rule;

class SchedulerMonthBuilder implements BuilderInterface
{
    /**
     * @throws InvalidSchedulerException
     */
    public function build(Rule $rule, SchedulerInterface $scheduler): Rule
    {
        try {
            $frequency = $scheduler->getScheduleMonthFrequency();

            $rule->setFreq('MONTHLY');

            if ($scheduler->isScheduledWeekDays()) {
                $days = SchedulerEnum::getWeekDays();
            } else {
                $days = [$scheduler->getScheduleDay()];
            }

            foreach ($days as $key => $day) {
                $days[$key] = $frequency.$day;
            }

            $rule->setByDay($days);
        } catch (InvalidArgument|InvalidRRule) {
            throw new InvalidSchedulerException();
        }

        return $rule;
    }
}
