<?php

namespace Mautic\ReportBundle\Scheduler;

use Recurr\Rule;

interface BuilderInterface
{
    public function build(Rule $rule, SchedulerInterface $scheduler);
}
