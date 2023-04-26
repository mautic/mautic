<?php

namespace Mautic\LeadBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 * @\Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor
 * @\Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor
 * @\Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor
 * @\Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor
 * @\Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor
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
