<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\ServiceJuggling;

final class SkippedJugglingService
{
    public function __construct(
        private readonly SomeUserHelper $userHelper,
        private readonly string $secret,
    ) {
    }

    public function run(SomeUserHelper $localUserHelper): void
    {
        // a value, not a service
        $this->handle($this->secret);

        // a param of its own, the service is not taken from the class
        $this->handleService($localUserHelper);

        // another service is the one being called, not a method of this class
        $this->userHelper->getUser();
    }

    public function handle(string $secret): void
    {
    }

    public function handleService(SomeUserHelper $userHelper): void
    {
    }
}
