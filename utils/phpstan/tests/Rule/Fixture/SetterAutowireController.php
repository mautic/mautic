<?php

declare(strict_types=1);

namespace MauticPhpStan\Tests\Rule\Fixture;

class SetterAutowireController
{
    #[\Symfony\Contracts\Service\Attribute\Required]
    public function setSomeModel(SomeModel $someModel): void
    {
    }
}
