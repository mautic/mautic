<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Utils\PHPStan\Rule\NoUselessReferenceForeachRule;

/**
 * @extends RuleTestCase<NoUselessReferenceForeachRule>
 */
final class NoUselessReferenceForeachRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NoUselessReferenceForeachRule($this->createReflectionProvider());
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__.'/Fixture/UselessReferenceForeach.php'], [
            [
                'Value "$widget" is referenced by "&" but never written to, so the reference has no effect. Remove the "&".',
                15,
            ],
            [
                'Value "$node" is referenced by "&" but never written to, so the reference has no effect. Remove the "&".',
                27,
            ],
        ]);
    }

    public function testSkipNeededReference(): void
    {
        $this->analyse([__DIR__.'/Fixture/NeededReferenceForeach.php'], []);
    }
}
