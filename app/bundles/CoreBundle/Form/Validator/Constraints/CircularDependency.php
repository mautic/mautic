<?php

namespace Mautic\CoreBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class CircularDependency extends Constraint
{
    public $message = 'mautic.lead_list.circular_dependency_detected';

    public function validatedBy(): string
    {
        return CircularDependencyValidator::class;
    }

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
