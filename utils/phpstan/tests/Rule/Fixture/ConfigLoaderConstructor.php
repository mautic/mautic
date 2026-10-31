<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

use Symfony\Component\Config\Loader\Loader;

final class ConfigLoaderConstructor
{
    public function __construct(
        private readonly Loader $loader,
    ) {
    }
}
