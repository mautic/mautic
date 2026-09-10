<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Utils\PHPStan\Rule\NoDuplicateNonRepeatableAttributeRule;

/**
 * @extends RuleTestCase<NoDuplicateNonRepeatableAttributeRule>
 */
final class NoDuplicateNonRepeatableAttributeRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NoDuplicateNonRepeatableAttributeRule($this->createReflectionProvider());
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__.'/Fixture/DuplicateNonRepeatableAttribute.php'], [
            [
                'Attribute "#[Utils\PHPStan\Tests\Rule\Fixture\NonRepeatableAttribute]" is used 2 times on the same class, but is not repeatable. Add the \Attribute::IS_REPEATABLE flag to its #[\Attribute] declaration, or remove the duplicate.',
                7,
            ],
            [
                'Attribute "#[Utils\PHPStan\Tests\Rule\Fixture\NonRepeatableAttribute]" is used 2 times on the same property, but is not repeatable. Add the \Attribute::IS_REPEATABLE flag to its #[\Attribute] declaration, or remove the duplicate.',
                11,
            ],
            [
                'Attribute "#[Utils\PHPStan\Tests\Rule\Fixture\NonRepeatableAttribute]" is used 2 times on the same method, but is not repeatable. Add the \Attribute::IS_REPEATABLE flag to its #[\Attribute] declaration, or remove the duplicate.',
                15,
            ],
        ]);
    }

    public function testSkipRepeatableAttribute(): void
    {
        $this->analyse([__DIR__.'/Fixture/DuplicateRepeatableAttribute.php'], []);
    }
}
