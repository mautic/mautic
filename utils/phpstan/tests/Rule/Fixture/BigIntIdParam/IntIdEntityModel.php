<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\BigIntIdParam;

final class IntIdEntityModel
{
    public function getEntity(?int $id = null): ?IntIdEntity
    {
        return null;
    }
}
