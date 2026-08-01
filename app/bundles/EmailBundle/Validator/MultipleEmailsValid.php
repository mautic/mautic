<?php

namespace Mautic\EmailBundle\Validator;

use Symfony\Component\Validator\Constraint;

final class MultipleEmailsValid extends Constraint
{
    public function getTargets(): string|array
    {
        return self::PROPERTY_CONSTRAINT;
    }
}
