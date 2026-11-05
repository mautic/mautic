<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

use Symfony\Component\DependencyInjection\ServiceLocator;

// a scoped ServiceLocator is allowed - must be skipped
class ServiceLocatorGetService
{
    public function __construct(
        private ServiceLocator $locator,
    ) {
    }

    public function run(): void
    {
        $this->locator->get('mautic.helper.something');
    }
}
