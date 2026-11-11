<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPStan\Type\FileTypeMapper;
use Utils\PHPStan\Rule\BoolNullPropertyDocblockToNativeTypeRule;

/**
 * @extends RuleTestCase<BoolNullPropertyDocblockToNativeTypeRule>
 */
final class BoolNullPropertyDocblockToNativeTypeRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new BoolNullPropertyDocblockToNativeTypeRule(
            self::getContainer()->getByType(FileTypeMapper::class)
        );
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__.'/Fixture/BoolNullPropertyDocblockToNativeType/SomeClass.php'], [
            [
                'Property "$isPreferenceCenter" has a "bool|null" docblock, so it must declare the native "?bool" type instead.',
                12,
            ],
            [
                'Property "$shortNullable" has a "bool|null" docblock, so it must declare the native "?bool" type instead.',
                17,
            ],
            [
                'Property "$inherited" has a "bool|null" docblock, so it must declare the native "?bool" type instead.',
                45,
            ],
        ]);
    }
}
