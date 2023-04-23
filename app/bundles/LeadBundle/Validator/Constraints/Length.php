<?php

namespace Mautic\LeadBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraints\Length as SymfonyLength;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 * @\Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor
 */
class Length extends SymfonyLength
{
    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return \get_class($this).'Validator';
    }
}
