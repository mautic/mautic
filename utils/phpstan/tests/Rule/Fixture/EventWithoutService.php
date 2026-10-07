<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

use Symfony\Contracts\EventDispatcher\Event;

final class EventWithoutService extends Event
{
    public function __construct(
        private readonly string $name,
        private readonly \DateTimeImmutable $createdAt,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
