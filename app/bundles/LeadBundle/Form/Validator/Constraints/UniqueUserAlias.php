<?php

namespace Mautic\LeadBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class UniqueUserAlias extends Constraint
{
    public $message = 'This alias is already in use.';

    public $field   = '';

    public function validatedBy(): string
    {
        return 'uniqueleadlist';
    }

    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }

    public function getRequiredOptions(): array
    {
        return ['field'];
    }

    public function getDefaultOption(): ?string
    {
        return 'field';
    }
}
