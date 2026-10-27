<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\ServiceJuggling;

trait SomeHandleTrait
{
    public function handleInTrait(SomeUserHelper $userHelper): void
    {
    }
}
