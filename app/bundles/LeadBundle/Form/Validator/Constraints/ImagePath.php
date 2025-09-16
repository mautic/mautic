<?php

namespace Mautic\LeadBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class ImagePath extends Constraint
{
    public function validatedBy(): string
    {
        return ImagePathValidator::class;
    }
}
