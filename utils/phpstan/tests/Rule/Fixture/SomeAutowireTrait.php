<?php

declare(strict_types=1);

namespace MauticPhpStan\Tests\Rule\Fixture;

use Symfony\Contracts\Service\Attribute\Required;

trait SomeAutowireTrait
{
    #[Required]
    public function setSomeModelOnTrait(SomeModel $someModel): void
    {
    }
}
