<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Utils\PHPStan\Rule\NoTablePrefixDefinitionInTestsRule;

/**
 * @extends RuleTestCase<NoTablePrefixDefinitionInTestsRule>
 */
final class NoTablePrefixDefinitionInTestsRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NoTablePrefixDefinitionInTestsRule();
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__.'/Fixture/DefineTablePrefixTest.php'], [
            [
                'Test must not define the "MAUTIC_TABLE_PREFIX" const, the test bootstrap defines it already. Remove the definition.',
                12,
            ],
            [
                'Test must not define the "MAUTIC_TABLE_PREFIX" const, the test bootstrap defines it already. Remove the definition.',
                18,
            ],
        ]);

        $this->analyse([__DIR__.'/Fixture/DefineOtherConstantTest.php'], []);
        $this->analyse([__DIR__.'/Fixture/TablePrefixDefiner.php'], []);
    }
}
