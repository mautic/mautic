<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule;

use PHPStan\Collectors\Collector;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Utils\PHPStan\Collector\ReturnObjectDocblockCollector;
use Utils\PHPStan\Rule\ReturnDocblockToNativeTypeRule;

/**
 * @extends RuleTestCase<ReturnDocblockToNativeTypeRule>
 */
final class ReturnDocblockToNativeTypeRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new ReturnDocblockToNativeTypeRule($this->createReflectionProvider());
    }

    /**
     * @return list<Collector>
     */
    protected function getCollectors(): array
    {
        return [new ReturnObjectDocblockCollector()];
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__.'/Fixture/ReturnObjectDocblock.php'], [
            [
                'Method "returnsObject()" has an "@return Utils\PHPStan\Tests\Rule\Fixture\SomeReturnObject" docblock but no native return type. Declare ": Utils\PHPStan\Tests\Rule\Fixture\SomeReturnObject" and drop the "@return" tag.',
                12,
            ],
            [
                'Method "returnsSelf()" has an "@return self" docblock but no native return type. Declare ": self" and drop the "@return" tag.',
                20,
            ],
            [
                'Method "returnsStatic()" has an "@return static" docblock but no native return type. Declare ": static" and drop the "@return" tag.',
                28,
            ],
            [
                'Method "returnsThis()" has an "@return static" docblock but no native return type. Declare ": static" and drop the "@return" tag.',
                36,
            ],
            [
                'Method "nullableReturn()" has an "@return ?Utils\PHPStan\Tests\Rule\Fixture\SomeReturnObject" docblock but no native return type. Declare ": ?Utils\PHPStan\Tests\Rule\Fixture\SomeReturnObject" and drop the "@return" tag.',
                44,
            ],
            [
                'Method "unionReturn()" has an "@return Utils\PHPStan\Tests\Rule\Fixture\SomeReturnObject|Utils\PHPStan\Tests\Rule\Fixture\AnotherReturnObject" docblock but no native return type. Declare ": Utils\PHPStan\Tests\Rule\Fixture\SomeReturnObject|Utils\PHPStan\Tests\Rule\Fixture\AnotherReturnObject" and drop the "@return" tag.',
                64,
            ],
            [
                'Method "nullableUnionReturn()" has an "@return Utils\PHPStan\Tests\Rule\Fixture\SomeReturnObject|Utils\PHPStan\Tests\Rule\Fixture\AnotherReturnObject|null" docblock but no native return type. Declare ": Utils\PHPStan\Tests\Rule\Fixture\SomeReturnObject|Utils\PHPStan\Tests\Rule\Fixture\AnotherReturnObject|null" and drop the "@return" tag.',
                72,
            ],
            [
                'Method "intersectionReturn()" has an "@return Countable&Traversable" docblock but no native return type. Declare ": Countable&Traversable" and drop the "@return" tag.',
                97,
            ],
            [
                'Method "iterableReturn()" has an "@return iterable" docblock but no native return type. Declare ": iterable" and drop the "@return" tag.',
                105,
            ],
            [
                'Method "generatorReturn()" has an "@return iterable" docblock but no native return type. Declare ": iterable" and drop the "@return" tag.',
                113,
            ],
        ]);
    }

    public function testPromoteWholeFamily(): void
    {
        $this->analyse([
            __DIR__.'/Fixture/ReturnFamily/BaseWithObjectReturn.php',
            __DIR__.'/Fixture/ReturnFamily/ChildOverridingObjectReturn.php',
        ], [
            [
                'Method "build()" has an "@return Utils\PHPStan\Tests\Rule\Fixture\SomeReturnObject" docblock but no native return type. Declare ": Utils\PHPStan\Tests\Rule\Fixture\SomeReturnObject" and drop the "@return" tag.',
                14,
            ],
            [
                'Method "build()" has an "@return Utils\PHPStan\Tests\Rule\Fixture\SomeReturnObject" docblock but no native return type. Declare ": Utils\PHPStan\Tests\Rule\Fixture\SomeReturnObject" and drop the "@return" tag.',
                14,
            ],
        ]);
    }

    public function testSkipBlockedFamily(): void
    {
        $this->analyse([
            __DIR__.'/Fixture/ReturnFamilyBlocked/BaseBlocked.php',
            __DIR__.'/Fixture/ReturnFamilyBlocked/ChildBlocked.php',
        ], []);
    }

    public function testSkipEntity(): void
    {
        $this->analyse([__DIR__.'/Fixture/Entity/EntityWithObjectReturn.php'], []);
    }

    public function testSkipEntityReturnType(): void
    {
        $this->analyse([__DIR__.'/Fixture/ServiceReturningEntity.php'], []);
    }
}
