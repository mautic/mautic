<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
final class UniqueUserAlias extends Constraint
{
    public $message = 'This alias is already in use.';

    public $field   = '';

    public function validatedBy(): string
    {
        return 'uniqueleadlist';
    }

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }

    public function getRequiredOptions(): array
    {
        return ['field'];
    }

    public function getDefaultOption(): string
    {
        return 'field';
    }
}
