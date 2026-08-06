<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
final class EmailAddress extends Constraint
{
    public function validatedBy(): string
    {
        return self::class.'Validator';
    }
}
