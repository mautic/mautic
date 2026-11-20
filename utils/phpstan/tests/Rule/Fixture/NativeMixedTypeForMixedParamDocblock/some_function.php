<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\NativeMixedTypeForMixedParamDocblock;

/**
 * @param mixed $value
 */
function some_mixed_function($value): void
{
}
