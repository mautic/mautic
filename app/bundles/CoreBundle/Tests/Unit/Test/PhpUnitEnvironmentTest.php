<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Test;

use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

final class PhpUnitEnvironmentTest extends TestCase
{
    #[RunInSeparateProcess]
    public function testIsolatedTestsUseTheTestEnvironment(): void
    {
        // KernelTestCase reads $_ENV before $_SERVER; both must override the production bootstrap.
        $this->assertSame('test', $_ENV['APP_ENV'] ?? null);
        $this->assertSame('test', $_SERVER['APP_ENV'] ?? null);
        $this->assertSame('test', getenv('APP_ENV'));
    }
}
