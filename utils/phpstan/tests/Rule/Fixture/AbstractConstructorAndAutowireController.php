<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

// an abstract controller is extended by others, both ways stay open
abstract class AbstractConstructorAndAutowireController
{
    public function __construct(SomeModel $someModel)
    {
    }

    #[\Symfony\Contracts\Service\Attribute\Required]
    public function autowireAbstractConstructorAndAutowireController(SomeModel $someModel): void
    {
    }
}
