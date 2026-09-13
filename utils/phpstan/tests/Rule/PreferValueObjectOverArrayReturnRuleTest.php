<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Utils\PHPStan\Rule\PreferValueObjectOverArrayReturnRule;

/**
 * @extends RuleTestCase<PreferValueObjectOverArrayReturnRule>
 */
final class PreferValueObjectOverArrayReturnRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new PreferValueObjectOverArrayReturnRule();
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__.'/Fixture/MultiValueArrayReturn.php'], [
            [
                'Method "twoKeyedValues()" returns a keyed array of 2 values; consider a dedicated value object instead.',
                11,
            ],
            [
                'Method "threeKeyedValues()" returns a keyed array of 3 values; consider a dedicated value object instead.',
                19,
            ],
        ]);
    }
}
