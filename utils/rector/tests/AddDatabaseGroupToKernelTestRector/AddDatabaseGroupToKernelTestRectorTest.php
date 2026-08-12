<?php

declare(strict_types=1);

namespace Utils\Rector\Tests\AddDatabaseGroupToKernelTestRector;

use PHPUnit\Framework\Attributes\DataProvider;
use Rector\Testing\PHPUnit\AbstractRectorTestCase;

final class AddDatabaseGroupToKernelTestRectorTest extends AbstractRectorTestCase
{
    #[DataProvider('provideData')]
    public function test(string $filePath): void
    {
        $this->doTestFile($filePath);
    }

    public static function provideData(): \Iterator
    {
        return self::yieldFilesFromDirectory(__DIR__.'/Fixture');
    }

    public function provideConfigFilePath(): string
    {
        return __DIR__.'/config/configured_rule.php';
    }
}
