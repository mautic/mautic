<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

final class MissingResponseReturnTypeController
{
    public function indexAction()
    {
        return null;
    }

    public function nameAction(): string
    {
        return 'name';
    }
}
