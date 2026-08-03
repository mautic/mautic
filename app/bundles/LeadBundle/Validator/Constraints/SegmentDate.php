<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

final class SegmentDate extends Constraint
{
    public string $message;

    public function validatedBy(): string
    {
        return SegmentDateValidator::class;
    }
}
