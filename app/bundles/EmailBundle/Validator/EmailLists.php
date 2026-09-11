<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
final class EmailLists extends Constraint
{
    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
