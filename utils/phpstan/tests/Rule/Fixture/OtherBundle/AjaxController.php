<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\OtherBundle;

use Symfony\Contracts\Service\Attribute\Required;
use Utils\PHPStan\Tests\Rule\Fixture\SomeModel;

class AjaxController
{
    #[Required]
    public function autowireOtherAjaxController(SomeModel $someModel): void
    {
    }
}
