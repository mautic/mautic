<?php

declare(strict_types=1);

namespace Utils\ECS\Tests\StandaloneLineAutowireParamFixer;

use PHPUnit\Framework\Attributes\DataProvider;
use Symplify\EasyCodingStandard\Testing\PHPUnit\AbstractCheckerTestCase;

// the ECS container builds a sniff processor on boot; its own autoload paths only resolve inside the ECS repository,
// so the code sniffer bundled with ECS has to be loaded here
require_once __DIR__.'/../../../../vendor/symplify/easy-coding-standard/vendor/squizlabs/php_codesniffer/autoload.php';

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
