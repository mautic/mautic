<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

final class NullableDateTimeConstructor
{
    public function __construct(
        private readonly ?\DateTimeInterface $dateFrom,
        private readonly ?\DateTimeImmutable $dateTo,
    ) {
    }
}
