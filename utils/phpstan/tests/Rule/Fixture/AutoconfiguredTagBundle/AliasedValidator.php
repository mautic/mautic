<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\AutoconfiguredTagBundle;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

final class AliasedValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
    }
}
