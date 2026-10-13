<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Utils\PHPStan\Rule\NoUselessReferenceParamRule;

/**
 * @extends RuleTestCase<NoUselessReferenceParamRule>
 */
final class NoUselessReferenceParamRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NoUselessReferenceParamRule($this->createReflectionProvider());
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__.'/Fixture/UselessReferenceParam.php'], [
            [
                'Parameter "$entity" is passed by reference but never written to, so the reference has no effect. Remove the "&".',
                9,
            ],
        ]);
    }

    public function testSkipNeededReference(): void
    {
        $this->analyse([__DIR__.'/Fixture/NeededReferenceParam.php'], []);
    }

    public function testSkipOverridingMethod(): void
    {
        $this->analyse([__DIR__.'/Fixture/OverridingReferenceParam.php'], []);
    }
}
