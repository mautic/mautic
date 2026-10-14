<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Utils\PHPStan\Rule\EntityParamMustHaveTypeRule;

/**
 * @extends RuleTestCase<EntityParamMustHaveTypeRule>
 */
final class EntityParamMustHaveTypeRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new EntityParamMustHaveTypeRule(self::getContainer()->getByType(\PHPStan\Type\FileTypeMapper::class));
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__.'/Fixture/EntityParamMustHaveType/SomeClass.php'], [
            [
                'Param "$entity" of "missingType()" must have a type, either a native type or a "@param" tag.',
                9,
            ],
            [
                'Param "$Entity" of "missingTypeUpperCase()" must have a type, either a native type or a "@param" tag.',
                13,
            ],
            [
                'Param "$entity" of "missingTypeWithOtherParamTag()" must have a type, either a native type or a "@param" tag.',
                35,
            ],
        ]);
    }
}
