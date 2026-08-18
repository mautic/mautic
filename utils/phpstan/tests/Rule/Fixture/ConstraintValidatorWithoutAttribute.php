<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

final class ConstraintValidatorWithoutAttribute extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
    }
}
