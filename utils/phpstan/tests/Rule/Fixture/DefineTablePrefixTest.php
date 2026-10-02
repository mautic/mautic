<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

// the test bootstrap defines the const already - must be reported
final class DefineTablePrefixTest
{
    public function setUp(): void
    {
        defined('MAUTIC_TABLE_PREFIX') or define('MAUTIC_TABLE_PREFIX', '');
    }

    public function setUpWithIf(): void
    {
        if (!defined('MAUTIC_TABLE_PREFIX')) {
            define('MAUTIC_TABLE_PREFIX', '');
        }
    }
}
