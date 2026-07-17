<?php

declare(strict_types=1);

namespace MauticPhpStan\Tests\Rule\Fixture;

class PlainSetterController
{
    public function setSomeModel(SomeModel $someModel): void
    {
    }
}
