<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

class SetterAutowireController
{
    #[\Symfony\Contracts\Service\Attribute\Required]
    public function setSomeModel(SomeModel $someModel): void
    {
    }
}
