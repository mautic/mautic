<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

final class UniqueCustomField extends Constraint
{
    public string $message = 'mautic.lead.field.unique.is_used';

    public string $object;

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
