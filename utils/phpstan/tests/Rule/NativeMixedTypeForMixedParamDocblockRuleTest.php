<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPStan\Type\FileTypeMapper;
use Utils\PHPStan\Rule\NativeMixedTypeForMixedParamDocblockRule;

/**
 * @extends RuleTestCase<NativeMixedTypeForMixedParamDocblockRule>
 */
final class NativeMixedTypeForMixedParamDocblockRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NativeMixedTypeForMixedParamDocblockRule(
            self::getContainer()->getByType(FileTypeMapper::class)
        );
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__.'/Fixture/NativeMixedTypeForMixedParamDocblock/SomeClass.php'], [
            [
                'Param "$value" of "missingMixed()" has a "mixed" docblock, so it must declare the native "mixed" type.',
                12,
            ],
            [
                'Param "$value" of "missingMixedWithNullDefault()" has a "mixed" docblock, so it must declare the native "mixed" type.',
                19,
            ],
            [
                'Param "$values" of "variadicMixed()" has a "mixed" docblock, so it must declare the native "mixed" type.',
                26,
            ],
            [
                'Param "$value" of "byRefMixed()" has a "mixed" docblock, so it must declare the native "mixed" type.',
                33,
            ],
        ]);
    }

    public function testFunction(): void
    {
        $this->analyse([__DIR__.'/Fixture/NativeMixedTypeForMixedParamDocblock/some_function.php'], [
            [
                'Param "$value" of "some_mixed_function()" has a "mixed" docblock, so it must declare the native "mixed" type.',
                10,
            ],
        ]);
    }
}
