<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Utils\PHPStan\Rule\SingleArgumentDispatchRule;

/**
 * @extends RuleTestCase<SingleArgumentDispatchRule>
 */
final class SingleArgumentDispatchRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new SingleArgumentDispatchRule();
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__.'/Fixture/DispatchEventService.php'], [
            [
                'Dispatch the event object alone: ->dispatch($event). The event class is the event name (Symfony 4.3+), so drop the CoreEvents::BUILD_MENU second argument.',
                21,
            ],
        ]);
    }
}
