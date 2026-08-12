<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Utils\PHPStan\Rule\KernelTestMustHaveDatabaseGroupRule;

/**
 * @extends RuleTestCase<KernelTestMustHaveDatabaseGroupRule>
 */
final class KernelTestMustHaveDatabaseGroupRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new KernelTestMustHaveDatabaseGroupRule($this->createReflectionProvider());
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__.'/Fixture/KernelTestMissingDatabaseGroup.php'], [
            [
                'Class "Utils\PHPStan\Tests\Rule\Fixture\KernelTestMissingDatabaseGroup" boots the kernel but is missing the #[Group(\'database\')] attribute.',
                9,
            ],
        ]);
    }

    public function testSkipKernelTestWithDatabaseGroup(): void
    {
        $this->analyse([__DIR__.'/Fixture/KernelTestWithDatabaseGroup.php'], []);
    }

    public function testSkipAbstractBaseKernelTest(): void
    {
        $this->analyse([__DIR__.'/Fixture/AbstractBaseKernelTest.php'], []);
    }

    public function testSkipPlainUnitTest(): void
    {
        $this->analyse([__DIR__.'/Fixture/PlainUnitTest.php'], []);
    }
}
