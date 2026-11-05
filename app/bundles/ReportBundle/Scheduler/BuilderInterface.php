<?php

declare(strict_types=1);

namespace Mautic\ReportBundle\Scheduler;

use Recurr\Rule;

interface BuilderInterface
{
    public function build(Rule $rule, SchedulerInterface $scheduler);
}
