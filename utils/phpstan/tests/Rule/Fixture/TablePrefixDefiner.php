<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

// not a test file - the const definition is allowed here
final class TablePrefixDefiner
{
    public function boot(): void
    {
        defined('MAUTIC_TABLE_PREFIX') or define('MAUTIC_TABLE_PREFIX', '');
    }
}
