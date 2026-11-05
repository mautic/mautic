<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPStan\Type\FileTypeMapper;
use Utils\PHPStan\Rule\NativeTypeForArrayOrIterableDocblockRule;

/**
 * @extends RuleTestCase<NativeTypeForArrayOrIterableDocblockRule>
 */
final class NativeTypeForArrayOrIterableDocblockRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NativeTypeForArrayOrIterableDocblockRule(
            self::getContainer()->getByType(FileTypeMapper::class)
        );
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__.'/Fixture/NativeTypeForArrayOrIterableDocblock/SomeClass.php'], [
            [
                'Param "$items" of "missingArrayParam()" has a "array" docblock, so it must declare the native "array" type.',
                12,
            ],
            [
                'Param "$values" of "missingIterableParam()" has a "iterable" docblock, so it must declare the native "iterable" type.',
                19,
            ],
            [
                'Param "$items" of "mixedArrayParam()" has a "array" docblock, so it must declare the native "array" type.',
                26,
            ],
            [
                'Method "missingArrayReturn()" has a "array" return docblock, so it must declare the native "array" return type.',
                33,
            ],
            [
                'Method "missingIterableReturn()" has a "iterable" return docblock, so it must declare the native "iterable" return type.',
                41,
            ],
            [
                'Param "$items" of "nullableArrayParam()" has a "?array" docblock, so it must declare the native "?array" type.',
                89,
            ],
            [
                'Method "nullableArrayReturn()" has a "?array" return docblock, so it must declare the native "?array" return type.',
                96,
            ],
            [
                'Method "unionArrayReturn()" has a "array" return docblock, so it must declare the native "array" return type.',
                104,
            ],
        ]);
    }

    public function testSkipOverriddenMethod(): void
    {
        $this->analyse([__DIR__.'/Fixture/NativeTypeForArrayOrIterableDocblock/SkipOverride.php'], []);
    }

    public function testNarrowedOverride(): void
    {
        // a concrete parent constructor is LSP-exempt and a private parent method imposes no contract,
        // so both narrowing child methods are still reported
        $this->analyse([__DIR__.'/Fixture/NativeTypeForArrayOrIterableDocblock/NarrowedOverride.php'], [
            [
                'Param "$items" of "__construct()" has a "array" docblock, so it must declare the native "array" type.',
                14,
            ],
            [
                'Param "$items" of "onlyPrivate()" has a "array" docblock, so it must declare the native "array" type.',
                21,
            ],
        ]);
    }

    public function testSkipConstructorBoundByAbstractParent(): void
    {
        // an abstract/interface constructor is a binding signature contract, so the override is skipped
        $this->analyse([__DIR__.'/Fixture/NativeTypeForArrayOrIterableDocblock/BoundConstructorOverride.php'], []);
    }
}
