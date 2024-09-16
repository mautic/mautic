<?php

namespace Mautic\CoreBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class CircularDependency extends Constraint
{
    public $message;

    public function validatedBy()
    {
        return CircularDependencyValidator::class;
    }
}
