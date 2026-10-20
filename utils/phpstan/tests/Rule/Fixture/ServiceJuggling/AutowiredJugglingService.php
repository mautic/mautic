<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\ServiceJuggling;

final class AutowiredJugglingService extends ParentJugglingService
{
    private SomeUserHelper $userHelper;

    public function autowireAutowiredJugglingService(SomeUserHelper $userHelper): void
    {
        $this->userHelper = $userHelper;
    }

    public function run(): void
    {
        parent::handle($this->userHelper);
    }
}
