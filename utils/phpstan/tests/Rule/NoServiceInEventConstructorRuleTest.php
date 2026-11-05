<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Utils\PHPStan\Rule\NoServiceInEventConstructorRule;

/**
 * @extends RuleTestCase<NoServiceInEventConstructorRule>
 */
final class NoServiceInEventConstructorRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NoServiceInEventConstructorRule($this->createReflectionProvider());
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__.'/Fixture/ServiceInEventConstructor.php'], [
            [
                'Service "$translator" of type "Symfony\Contracts\Translation\TranslatorInterface" is only carried by this event and handed to its listeners. Inject it in the listener instead of passing it through the event.',
                14,
            ],
        ]);
    }

    public function testSkipServiceUsedInternally(): void
    {
        $this->analyse([__DIR__.'/Fixture/ServiceUsedInternallyEvent.php'], []);
    }

    public function testSkipEventWithoutService(): void
    {
        $this->analyse([__DIR__.'/Fixture/EventWithoutService.php'], []);
    }

    public function testSkipNonEventClass(): void
    {
        $this->analyse([__DIR__.'/Fixture/ServiceInNonEventConstructor.php'], []);
    }
}
