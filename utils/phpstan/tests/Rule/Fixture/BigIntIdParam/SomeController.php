<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\BigIntIdParam;

final class SomeController
{
    public function editAction(int $id): void
    {
    }

    public function viewAction(?int $objectId): void
    {
    }

    public function okAction(int|string $id, int|string $objectId): void
    {
    }

    public function untypedAction($id, $objectId): void
    {
    }

    public function otherParamAction(int $page): void
    {
    }
}
