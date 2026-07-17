<?php

declare(strict_types=1);

namespace Utils\ECS\Tests\StandaloneLineAutowireParamFixer;

use PHPUnit\Framework\Attributes\DataProvider;
use Symplify\EasyCodingStandard\Testing\PHPUnit\AbstractCheckerTestCase;

final class StandaloneLineAutowireParamFixerTest extends AbstractCheckerTestCase
{
    #[DataProvider('provideData')]
    public function test(string $filePath): void
    {
        $this->doTestFile($filePath);
    }

    public static function provideData(): \Iterator
    {
        return self::yieldFiles(__DIR__.'/Fixture');
    }

    public function provideConfig(): string
    {
        return __DIR__.'/config/configured_rule.php';
    }
}
