<?php

declare(strict_types=1);

namespace MauticPhpStan\Tests\Rule\Fixture;

use Symfony\Contracts\Service\Attribute\Required;

class NamedAutowireController
{
    #[Required]
    public function autowireNamedAutowireController(SomeModel $someModel): void
    {
    }
}
