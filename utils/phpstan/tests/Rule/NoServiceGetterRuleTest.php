<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Utils\PHPStan\Rule\NoServiceGetterRule;

/**
 * @extends RuleTestCase<NoServiceGetterRule>
 */
final class NoServiceGetterRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NoServiceGetterRule();
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__.'/Fixture/ServiceGetter.php'], [
            [
                'Public getter "getRepository()" returns service "Utils\PHPStan\Tests\Rule\Fixture\SomeRepository". Inject the service where it is used instead of exposing it.',
                35,
            ],
            [
                'Public getter "getModel()" returns service "Utils\PHPStan\Tests\Rule\Fixture\SomeModel". Inject the service where it is used instead of exposing it.',
                40,
            ],
        ]);
    }
}
