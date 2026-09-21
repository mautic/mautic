<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

final class PlainSetterController
{
    public function setSomeModel(SomeModel $someModel): void
    {
    }
}
