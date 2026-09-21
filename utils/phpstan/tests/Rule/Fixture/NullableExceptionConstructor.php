<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

final class NullableExceptionConstructor extends \Exception
{
    public function __construct(
        string $message = '',
        int $code = 0,
        private readonly ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
