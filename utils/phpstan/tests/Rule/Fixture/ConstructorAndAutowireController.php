<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

use Symfony\Contracts\Service\Attribute\Required;

// the constructor is right here, so the setter is not needed - must be reported
final class ConstructorAndAutowireController
{
    public function __construct(SomeModel $someModel)
    {
    }

    #[Required]
    public function autowireConstructorAndAutowireController(
        SomeModel $someModel,
    ): void {
    }
}
