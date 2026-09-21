<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Utils\PHPStan\Rule\ConstraintMustHaveAttributeRule;

/**
 * @extends RuleTestCase<ConstraintMustHaveAttributeRule>
 */
final class ConstraintMustHaveAttributeRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new ConstraintMustHaveAttributeRule($this->createReflectionProvider());
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__.'/Fixture/ConstraintMissingAttribute.php'], [
            [
                'Class "Utils\PHPStan\Tests\Rule\Fixture\ConstraintMissingAttribute" extends Constraint but is missing the #[\Attribute] attribute. Add it, so the constraint can be used as an attribute on properties, as Symfony convention.',
                9,
            ],
        ]);
    }

    public function testSkipConstraintWithAttribute(): void
    {
        $this->analyse([__DIR__.'/Fixture/ConstraintWithAttribute.php'], []);
    }

    public function testSkipAbstractBaseConstraint(): void
    {
        $this->analyse([__DIR__.'/Fixture/AbstractBaseConstraint.php'], []);
    }

    public function testSkipConstraintValidator(): void
    {
        $this->analyse([__DIR__.'/Fixture/ConstraintValidatorWithoutAttribute.php'], []);
    }
}
