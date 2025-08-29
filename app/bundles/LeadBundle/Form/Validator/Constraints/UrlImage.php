<?php

namespace Mautic\LeadBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class UrlImage extends Constraint
{
    public function validatedBy(): string
    {
        return UrlImageValidator::class;
    }
}
