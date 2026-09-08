<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

final class ReadonlyApiParam
{
    /**
     * @param \stdClass $service @api cannot be readonly as changed in tests via reflection
     */
    public function __construct(
        private readonly \stdClass $service,
        private readonly int $count,
    ) {
    }
}
