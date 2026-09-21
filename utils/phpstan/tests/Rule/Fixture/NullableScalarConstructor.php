<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

final class NullableScalarConstructor
{
    /**
     * @param string[]|null $options
     */
    public function __construct(
        private readonly ?string $name,
        private readonly ?int $count,
        private readonly ?array $options,
        private readonly SomeModel $someModel,
    ) {
    }
}
