<?php

declare(strict_types=1);

namespace Mautic\ReportBundle\Scheduler\Builder;

use Mautic\ReportBundle\Scheduler\BuilderInterface;
use Mautic\ReportBundle\Scheduler\Exception\InvalidSchedulerException;
use Mautic\ReportBundle\Scheduler\SchedulerInterface;
use Recurr\Exception\InvalidArgument;
use Recurr\Rule;

class SchedulerNowBuilder implements BuilderInterface
{
    /**
     * @throws InvalidSchedulerException
     */
    public function build(Rule $rule, SchedulerInterface $scheduler): Rule
    {
        try {
            $rule->setFreq('SECONDLY');
        } catch (InvalidArgument) {
            throw new InvalidSchedulerException();
        }

        return $rule;
    }
}
