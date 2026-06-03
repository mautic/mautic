<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Validator;

use Symfony\Component\Validator\Constraint;

final class PageHit extends Constraint
{
    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
