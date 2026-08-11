<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Utils\PHPStan\Rule\NoAutoconfiguredServiceTagRule;

/**
 * @extends RuleTestCase<NoAutoconfiguredServiceTagRule>
 */
final class NoAutoconfiguredServiceTagRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NoAutoconfiguredServiceTagRule($this->createReflectionProvider());
    }

    public function testRule(): void
    {
        $this->analyse([
            __DIR__.'/Fixture/AutoconfiguredTagBundle/Config/services.php',
        ], [
            [
                'Tag "kernel.event_subscriber" is added by autoconfigure() on its own, as "Utils\PHPStan\Tests\Rule\Fixture\AutoconfiguredTagBundle\SomeSubscriber" is a Symfony\Component\EventDispatcher\EventSubscriberInterface - remove the ->tag() call.',
                18,
            ],
            [
                'Tag "validator.constraint_validator" is added by autoconfigure() on its own, as "Utils\PHPStan\Tests\Rule\Fixture\AutoconfiguredTagBundle\SomeValidator" is a Symfony\Component\Validator\ConstraintValidatorInterface - remove the ->tag() call.',
                19,
            ],
            [
                'Tag "validator.constraint_validator" is added by autoconfigure() on its own, as "Utils\PHPStan\Tests\Rule\Fixture\AutoconfiguredTagBundle\SomeValidator" is a Symfony\Component\Validator\ConstraintValidatorInterface - remove the ->tag() call.',
                22,
            ],
            [
                'Tag "kernel.event_subscriber" is added by autoconfigure() on its own, as "Utils\PHPStan\Tests\Rule\Fixture\AutoconfiguredTagBundle\SomeSubscriber" is a Symfony\Component\EventDispatcher\EventSubscriberInterface - remove the ->tag() call.',
                26,
            ],
        ]);
    }

    public function testSkipConfigWithoutAutoconfigure(): void
    {
        $this->analyse([
            __DIR__.'/Fixture/NoAutoconfigureBundle/Config/services.php',
        ], []);
    }
}
