<?php

namespace Mautic\LeadBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class EmailAddress extends Constraint
{
    public function validatedBy()
    {
        return static::class.'Validator';
    }
}
