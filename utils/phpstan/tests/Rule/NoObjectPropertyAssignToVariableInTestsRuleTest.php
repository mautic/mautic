<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Utils\PHPStan\Rule\NoObjectPropertyAssignToVariableInTestsRule;

/**
 * @extends RuleTestCase<NoObjectPropertyAssignToVariableInTestsRule>
 */
final class NoObjectPropertyAssignToVariableInTestsRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NoObjectPropertyAssignToVariableInTestsRule();
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__.'/Fixture/AssignObjectPropertyTest.php'], [
            [
                'Test must not assign the object property "$this->repositoryMock" to a variable. Use the property directly instead.',
                15,
            ],
        ]);

        $this->analyse([__DIR__.'/Fixture/AssignObjectPropertyService.php'], []);
    }
}
