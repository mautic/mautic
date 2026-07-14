<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Validator;

use Symfony\Component\Validator\Constraint;

final class ValidEmailLinks extends Constraint
{
    public string $message = 'mautic.email.links.invalid';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
