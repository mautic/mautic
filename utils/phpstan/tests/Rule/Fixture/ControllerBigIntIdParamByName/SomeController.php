<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\ControllerBigIntIdParamByName;

final class SomeController
{
    public function indexAction(int $leadId): void
    {
    }

    public function nullableAction(?int $contactId): void
    {
    }

    public function filterAction(int $formId, int $contactId): void
    {
    }

    public function skipIntOrStringAction(int|string $leadId): void
    {
    }

    public function skipUntypedAction($leadId): void
    {
    }

    public function skipUnknownNameAction(int $id): void
    {
    }
}
