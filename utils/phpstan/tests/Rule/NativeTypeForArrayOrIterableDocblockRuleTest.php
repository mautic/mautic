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

    public function testFunctions(): void
    {
        $this->analyse([__DIR__.'/Fixture/NativeTypeForArrayOrIterableDocblock/some_functions.php'], [
            [
                'Param "$items" of "missingArrayParamFunction()" has a "array" docblock, so it must declare the native "array" type.',
                10,
            ],
            [
                'Method "missingIterableReturnFunction()" has a "iterable" return docblock, so it must declare the native "iterable" return type.',
                17,
            ],
        ]);
    }

    public function testSkipOverriddenMethod(): void
    {
        $this->analyse([__DIR__.'/Fixture/NativeTypeForArrayOrIterableDocblock/SkipOverride.php'], []);
    }
}
