<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

#[\Attribute(\Attribute::TARGET_ALL | \Attribute::IS_REPEATABLE)]
final class RepeatableAttribute
{
}
