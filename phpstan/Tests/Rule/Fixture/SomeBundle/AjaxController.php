<?php

declare(strict_types=1);

namespace MauticPhpStan\Tests\Rule\Fixture\SomeBundle;

use MauticPhpStan\Tests\Rule\Fixture\SomeModel;
use Symfony\Contracts\Service\Attribute\Required;

class AjaxController
{
    #[Required]
    public function autowireAjaxController(SomeModel $someModel): void
    {
    }
}
