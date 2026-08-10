<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
final class ConstraintWithAttribute extends Constraint
{
}
