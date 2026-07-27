<?php

namespace Mautic\CoreBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

final class CircularDependency extends Constraint
{
    public $message;

    public function validatedBy(): string
    {
        return CircularDependencyValidator::class;
    }
}
