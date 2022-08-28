<?php

namespace Mautic\LeadBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class EmailAddress extends Constraint
{
    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return \get_class($this).'Validator';
    }
}
