<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Validator;

use Symfony\Component\Validator\Constraint;

class ScheduleDateRange extends Constraint
{
    public string $message = 'mautic.form.date_time_range.invalid_range';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
