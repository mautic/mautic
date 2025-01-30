<?php

namespace Mautic\LeadBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class EmailAddress extends Constraint
{
    public function validatedBy(): string
    {
        return static::class.'Validator';
    }
}
