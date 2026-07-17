<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

class DefineOtherConstantTest
{
    public function setUp(): void
    {
        defined('MAUTIC_ENV') or define('MAUTIC_ENV', 'test');
    }
}
