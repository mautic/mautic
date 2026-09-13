<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Utils\PHPStan\Rule\RequireArrayShapeReturnRule;

/**
 * @extends RuleTestCase<RequireArrayShapeReturnRule>
 */
final class RequireArrayShapeReturnRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new RequireArrayShapeReturnRule();
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__.'/Fixture/MultiValueArrayReturn.php'], [
            [
                'Method "twoKeyedValues()" returns a keyed array of 2 values; declare its shape in @return, e.g. array{key: type}.',
                31,
            ],
            [
                'Method "threeKeyedValues()" returns a keyed array of 3 values; declare its shape in @return, e.g. array{key: type}.',
                39,
            ],
        ]);
    }

    public function testSkipsDeclaredArrayShape(): void
    {
        $this->analyse([__DIR__.'/Fixture/DeclaredArrayShapeReturn.php'], []);
    }

    public function testFlagsTestClasses(): void
    {
        $this->analyse([__DIR__.'/Fixture/MultiValueArrayReturnInTest.php'], [
            [
                'Method "twoKeyedValues()" returns a keyed array of 2 values; declare its shape in @return, e.g. array{key: type}.',
                11,
            ],
        ]);
    }

    public function testSkipsAnonymousClasses(): void
    {
        $this->analyse([__DIR__.'/Fixture/MultiValueArrayReturnInAnonymousClass.php'], []);
    }

    public function testSkipsMethodOverridingParent(): void
    {
        $this->analyse([
            __DIR__.'/Fixture/OverriddenParent/AbstractDynamicLabelResolver.php',
            __DIR__.'/Fixture/OverriddenParent/OverriddenChildArrayReturn.php',
        ], []);
    }
}
