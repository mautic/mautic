<?php

declare(strict_types=1);

namespace MauticPhpStan\Tests\Rule\Fixture;

use Symfony\Contracts\Service\Attribute\Required;

class SomeAutowireService
{
    #[Required]
    public function setSomeModel(SomeModel $someModel): void
    {
    }
}
