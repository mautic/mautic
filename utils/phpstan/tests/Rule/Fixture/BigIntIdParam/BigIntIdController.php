<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\BigIntIdParam;

final class BigIntIdController
{
    public function __construct(
        private BigIntIdEntityModel $bigIntIdEntityModel,
        private IntIdEntityModel $intIdEntityModel,
    ) {
    }

    public function editAction(int $id): void
    {
        $this->bigIntIdEntityModel->getEntity($id);
    }

    public function nullableAction(?int $leadId): void
    {
        $this->bigIntIdEntityModel->getEntity($leadId);
    }

    public function skipIntEntityAction(int $id): void
    {
        $this->intIdEntityModel->getEntity($id);
    }

    public function skipIntOrStringAction(int|string $id): void
    {
        $this->bigIntIdEntityModel->getEntity($id);
    }

    public function skipUntypedAction($id): void
    {
        $this->bigIntIdEntityModel->getEntity($id);
    }

    public function skipUnusedParamAction(int $page): void
    {
        $this->bigIntIdEntityModel->getEntity(1);
    }

    public function thisMethodAction(int $contactId): void
    {
        $this->checkAccess($contactId);
    }

    private function checkAccess(int|string $id): ?BigIntIdEntity
    {
        return $this->bigIntIdEntityModel->getEntity($id);
    }
}
