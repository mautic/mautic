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
        $errorMessage = 'Param "$%s" of "%s()" must have a native type hint, not just a "@param" tag.';

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
                sprintf($errorMessage, 'entity', 'paramTagOnly'),
                34,
            ],
        ]);
    }
}
