<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

use Symfony\Contracts\Service\Attribute\Required;

final class NamedAutowireController
{
    #[Required]
    public function autowireNamedAutowireController(
        SomeModel $someModel,
    ): void {
    }
}
