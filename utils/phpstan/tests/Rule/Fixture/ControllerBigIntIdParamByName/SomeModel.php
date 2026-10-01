<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\ControllerBigIntIdParamByName;

final class SomeModel
{
    public function indexAction(int $leadId): void
    {
    }
}
