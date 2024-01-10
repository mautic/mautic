<?php

namespace Mautic\CoreBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class CircularDependency extends Constraint
{
    public $message = 'mautic.lead_list.is_in_use';

    public function validatedBy()
    {
        return CircularDependencyValidator::class;
    }

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
