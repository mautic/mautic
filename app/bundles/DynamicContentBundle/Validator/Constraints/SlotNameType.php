<?php

declare(strict_types=1);

namespace Mautic\DynamicContentBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * This constraint makes sure all entities with same slot name have the same type.
 */
class SlotNameType extends Constraint
{
    public string $message = 'mautic.dynamicContent.slot_name_type';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
