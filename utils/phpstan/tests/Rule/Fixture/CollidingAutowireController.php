<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

use Symfony\Contracts\Service\Attribute\Required;

final class CollidingAutowireController
{
    #[Required]
    public function autowire(
        SomeModel $someModel,
    ): void {
    }
}
