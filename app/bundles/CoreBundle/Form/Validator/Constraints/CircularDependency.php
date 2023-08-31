<?php

namespace Mautic\CoreBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @deprecated to be removed in Mautic 6.0, use SegmentInUse instead
 */
class CircularDependency extends Constraint
{
    public $message;

    public function validatedBy()
    {
        return CircularDependencyValidator::class;
    }
}
