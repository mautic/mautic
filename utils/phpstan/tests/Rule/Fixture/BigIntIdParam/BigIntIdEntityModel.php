<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\BigIntIdParam;

final class BigIntIdEntityModel
{
    public function getEntity(int|string|null $id = null): ?BigIntIdEntity
    {
        return null;
    }
}
