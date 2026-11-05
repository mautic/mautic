<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPStan\Type\FileTypeMapper;
use Utils\PHPStan\Rule\NativeUnionParamMustHaveUnionDocblockRule;

/**
 * @extends RuleTestCase<NativeUnionParamMustHaveUnionDocblockRule>
 */
final class NativeUnionParamMustHaveUnionDocblockRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NativeUnionParamMustHaveUnionDocblockRule(
            self::getContainer()->getByType(FileTypeMapper::class)
        );
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__.'/Fixture/NativeUnionParamDocblock/SomeFormFactory.php'], [
            [
                'Param "$entity" of "createNarrowed()" has native union type "object|array" but its "@param" docblock type "Utils\PHPStan\Tests\Rule\Fixture\NativeUnionParamDocblock\SomeEntity" is not a union; the "@param" must also be a union.',
                12,
            ],
            [
                'Param "$id" of "createNarrowedScalar()" has native union type "int|string" but its "@param" docblock type "int" is not a union; the "@param" must also be a union.',
                19,
            ],
        ]);
    }
}
