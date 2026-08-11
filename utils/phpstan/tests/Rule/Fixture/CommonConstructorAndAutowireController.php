<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

use Symfony\Contracts\Service\Attribute\Required;

// a common controller is extended by others, both ways stay open
final class CommonConstructorAndAutowireController
{
    public function __construct(SomeModel $someModel)
    {
    }

    #[Required]
    public function autowireCommonConstructorAndAutowireController(
        SomeModel $someModel,
    ): void {
    }
}
