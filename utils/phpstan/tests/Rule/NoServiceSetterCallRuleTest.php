<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Utils\PHPStan\Rule\NoServiceSetterCallRule;

/**
 * @extends RuleTestCase<NoServiceSetterCallRule>
 */
final class NoServiceSetterCallRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NoServiceSetterCallRule();
    }

    public function testRule(): void
    {
        $this->analyse([
            __DIR__.'/Fixture/SetterCallBundle/Config/services.php',
            __DIR__.'/Fixture/SetterCallBundle/Repository.php',
            __DIR__.'/Fixture/SetterCallBundle/SomeService.php',
        ], [
            [
                'Setter call() to "setRepository()" wires the dependency by hand, mark the method #[Required] and let autowiring call it instead.',
                16,
            ],
            [
                'Setter call() to "setUniqueIdentifiersOperator()" wires the dependency by hand, mark the method #[Required] and let autowiring call it instead.',
                20,
            ],
        ]);
    }
}
