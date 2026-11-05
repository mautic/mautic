<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\ServiceJuggling;

final class TraitJugglingService
{
    use SomeHandleTrait;

    public function __construct(
        private readonly SomeUserHelper $userHelper,
    ) {
    }

    public function run(): void
    {
        // the trait has no constructor of its own, so the service can only travel as a parameter
        $this->handleInTrait($this->userHelper);
    }
}
