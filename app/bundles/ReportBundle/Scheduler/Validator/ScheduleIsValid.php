<?php

namespace Mautic\ReportBundle\Scheduler\Validator;

use Symfony\Component\Validator\Constraint;

final class ScheduleIsValid extends Constraint
{
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
