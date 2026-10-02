<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\ServiceJuggling;

abstract class ParentJugglingService
{
    public function handle(SomeUserHelper $userHelper): void
    {
    }
}
