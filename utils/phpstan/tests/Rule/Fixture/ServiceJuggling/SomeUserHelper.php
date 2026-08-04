<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\ServiceJuggling;

final class SomeUserHelper
{
    public function getUser(): ?string
    {
        return null;
    }
}
