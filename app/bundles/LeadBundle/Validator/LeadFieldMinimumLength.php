<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Validator;

use Symfony\Component\Validator\Constraint;

final class LeadFieldMinimumLength extends Constraint
{
    public string $message = 'mautic.lead.field.char_length_limit.too_short';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
