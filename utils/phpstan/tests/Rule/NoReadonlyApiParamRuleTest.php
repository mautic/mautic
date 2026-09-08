<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Utils\PHPStan\Rule\NoReadonlyApiParamRule;

/**
 * @extends RuleTestCase<NoReadonlyApiParamRule>
 */
final class NoReadonlyApiParamRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NoReadonlyApiParamRule();
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__.'/Fixture/ReadonlyApiParam.php'], [
            [
                'Parameter "$service" is marked "@api" and may be swapped via reflection in tests, so it must not be readonly. Remove the readonly modifier.',
                13,
            ],
        ]);
    }

    public function testSkipNonReadonlyApiParam(): void
    {
        $this->analyse([__DIR__.'/Fixture/NonReadonlyApiParam.php'], []);
    }
}
