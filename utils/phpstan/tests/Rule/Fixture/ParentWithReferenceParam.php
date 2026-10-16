<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

abstract class ParentWithReferenceParam
{
    public function handle(object &$item): void
    {
        $item = new \stdClass();
    }
}
