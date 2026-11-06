<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPStan\Type\FileTypeMapper;
use Utils\PHPStan\Rule\NativeTypeForArrayOrIterablePropertyRule;

/**
 * @extends RuleTestCase<NativeTypeForArrayOrIterablePropertyRule>
 */
final class NativeTypeForArrayOrIterablePropertyRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NativeTypeForArrayOrIterablePropertyRule(
            self::getContainer()->getByType(FileTypeMapper::class)
        );
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__.'/Fixture/NativeTypeForArrayOrIterableProperty/SomeProperties.php'], [
            [
                'Property "$items" has a "array" docblock, so it must declare the native "array" type.',
                15,
            ],
            [
                'Property "$values" has a "iterable" docblock, so it must declare the native "iterable" type.',
                20,
            ],
            [
                'Property "$mixedItems" has a "array" docblock, so it must declare the native "array" type.',
                30,
            ],
        ]);
    }
}
