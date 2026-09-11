<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\SomeBundle;

use Symfony\Contracts\Service\Attribute\Required;
use Utils\PHPStan\Tests\Rule\Fixture\SomeModel;

final class AjaxController
{
    #[Required]
    public function autowireAjaxController(
        SomeModel $someModel,
    ): void {
    }
}
