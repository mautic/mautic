<?php

declare(strict_types=1);

namespace Mautic\AssetBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

final class Upload extends Constraint
{
    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
