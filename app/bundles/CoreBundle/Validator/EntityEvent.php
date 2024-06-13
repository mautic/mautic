<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Validator;

use Symfony\Component\Validator\Constraint;

final class EntityEvent extends Constraint
{
    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
