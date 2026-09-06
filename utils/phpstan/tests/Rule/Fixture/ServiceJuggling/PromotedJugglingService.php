<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\ServiceJuggling;

final class PromotedJugglingService
{
    public function __construct(
        private readonly SomeUserHelper $userHelper,
    ) {
    }

    public function run(): void
    {
        $this->handle($this->userHelper);
    }

    public function handle(SomeUserHelper $userHelper): void
    {
    }
}
