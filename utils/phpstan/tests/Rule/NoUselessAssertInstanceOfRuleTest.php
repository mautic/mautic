<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Utils\PHPStan\Rule\NoUselessAssertInstanceOfRule;

/**
 * @extends RuleTestCase<NoUselessAssertInstanceOfRule>
 */
final class NoUselessAssertInstanceOfRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NoUselessAssertInstanceOfRule();
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__.'/Fixture/NoUselessAssertInstanceOf/UselessAssertInstanceOf.php'], [
            [
                'Value is already known to be "ArrayObject", remove the useless assertInstanceOf(), or if the type comes only from a @var docblock, remove the @var instead.',
                14,
            ],
            [
                'Value is already known to be "Countable", remove the useless assertInstanceOf(), or if the type comes only from a @var docblock, remove the @var instead.',
                16,
            ],
        ]);
    }
}
