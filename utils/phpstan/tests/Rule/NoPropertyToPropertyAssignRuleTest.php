<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Utils\PHPStan\Rule\NoPropertyToPropertyAssignRule;

/**
 * @extends RuleTestCase<NoPropertyToPropertyAssignRule>
 */
final class NoPropertyToPropertyAssignRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NoPropertyToPropertyAssignRule();
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__.'/Fixture/PropertyToPropertyAssignService.php'], [
            [
                'Property "$this->repository" must not be assigned from property "$this->someRepository". Use the original property directly instead.',
                18,
            ],
        ]);

        $this->analyse([__DIR__.'/Fixture/PropertyToPropertyAssignAnonymousClass.php'], []);

        $this->analyse([__DIR__.'/Fixture/AssignObjectPropertyService.php'], []);
    }
}
