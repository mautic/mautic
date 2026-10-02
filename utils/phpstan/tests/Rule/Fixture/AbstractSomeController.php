<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

abstract class AbstractSomeController
{
    public function indexAction()
    {
        return [];
    }
}
