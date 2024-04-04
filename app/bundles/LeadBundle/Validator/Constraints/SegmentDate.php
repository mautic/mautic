<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class SegmentDate extends Constraint
{
    public string $message;

    public function validatedBy()
    {
        return SegmentDateValidator::class;
    }
}
