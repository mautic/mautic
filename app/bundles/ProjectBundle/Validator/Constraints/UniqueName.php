<?php

declare(strict_types=1);

namespace Mautic\ProjectBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

final class UniqueName extends Constraint
{
    public string $message = 'project.name.already.exists';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
