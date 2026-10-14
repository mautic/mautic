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
        return new EntityParamMustHaveTypeRule();
    }

    public function testRule(): void
    {
        $errorMessage = 'Param "$%s" of "%s()" must have a native object type hint ("object", a class, or a union that includes one), not just a "@param" tag.';

        $this->analyse([__DIR__.'/Fixture/EntityParamMustHaveType/SomeClass.php'], [
            [
                sprintf($errorMessage, 'entity', 'missingType'),
                9,
            ],
            [
                sprintf($errorMessage, 'Entity', 'missingTypeUpperCase'),
                13,
            ],
            [
                sprintf($errorMessage, 'entity', 'mixedNativeType'),
                17,
            ],
            [
                sprintf($errorMessage, 'entity', 'scalarNativeType'),
                21,
            ],
            [
                sprintf($errorMessage, 'entity', 'arrayNativeType'),
                25,
            ],
            [
                sprintf($errorMessage, 'entity', 'paramTagOnly'),
                34,
            ],
        ]);
    }
}
