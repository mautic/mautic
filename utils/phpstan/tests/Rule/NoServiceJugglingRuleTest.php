<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Utils\PHPStan\Rule\NoServiceJugglingRule;

/**
 * @extends RuleTestCase<NoServiceJugglingRule>
 */
final class NoServiceJugglingRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NoServiceJugglingRule();
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__.'/Fixture/ServiceJuggling/PromotedJugglingService.php'], [
            [
                'Service "$this->userHelper" is passed to "handle()" as an argument. Inject "Utils\PHPStan\Tests\Rule\Fixture\ServiceJuggling\SomeUserHelper" in the constructor of the class that uses it instead.',
                16,
            ],
        ]);

        $this->analyse([
            __DIR__.'/Fixture/ServiceJuggling/ParentJugglingService.php',
            __DIR__.'/Fixture/ServiceJuggling/AutowiredJugglingService.php',
        ], [
            [
                'Service "$this->userHelper" is passed to "handle()" as an argument. Inject "Utils\PHPStan\Tests\Rule\Fixture\ServiceJuggling\SomeUserHelper" in the constructor of the class that uses it instead.',
                18,
            ],
        ]);

        $this->analyse([__DIR__.'/Fixture/ServiceJuggling/SkippedJugglingService.php'], []);
        $this->analyse([__DIR__.'/Fixture/ServiceJuggling/TraitJugglingService.php'], []);
        $this->analyse([__DIR__.'/Fixture/ServiceJuggling/VaryingArgumentJugglingService.php'], []);

        $this->analyse([
            __DIR__.'/Fixture/ServiceJuggling/ParentJugglingService.php',
            __DIR__.'/Fixture/ServiceJuggling/InheritedMethodJugglingService.php',
        ], []);
    }
}
