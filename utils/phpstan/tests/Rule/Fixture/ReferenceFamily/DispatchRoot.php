<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\ReferenceFamily;

abstract class DispatchRoot
{
    protected function dispatch(string $action, object &$entity): string
    {
        return $action;
    }
}
