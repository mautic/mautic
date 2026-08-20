<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\SetterCallBundle;

final class SomeService
{
    public function setRepository(Repository $repository): void
    {
    }

    public function configure(Repository $repository): void
    {
    }
}
