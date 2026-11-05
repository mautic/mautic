<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\EntityParamConcreteType;

final class PlainService
{
    public function bareObject(object $entity): void
    {
    }
}
