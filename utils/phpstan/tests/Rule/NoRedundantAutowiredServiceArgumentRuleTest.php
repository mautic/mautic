<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Utils\PHPStan\Rule\NoRedundantAutowiredServiceArgumentRule;

/**
 * @extends RuleTestCase<NoRedundantAutowiredServiceArgumentRule>
 */
final class NoRedundantAutowiredServiceArgumentRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NoRedundantAutowiredServiceArgumentRule($this->createReflectionProvider());
    }

    public function testRule(): void
    {
        $this->analyse([
            __DIR__.'/Fixture/AutowiredArgumentBundle/Config/services.php',
            __DIR__.'/Fixture/AutowiredArgumentBundle/Bar.php',
            __DIR__.'/Fixture/AutowiredArgumentBundle/Baz.php',
            __DIR__.'/Fixture/AutowiredArgumentBundle/Foo.php',
            __DIR__.'/Fixture/AutowiredArgumentBundle/TwoArg.php',
            __DIR__.'/Fixture/AutowiredArgumentBundle/NamedArgumentService.php',
            __DIR__.'/Fixture/AutowiredArgumentBundle/BarInterface.php',
            __DIR__.'/Fixture/AutowiredArgumentBundle/NeedsInterface.php',
        ], [
            [
                'Every argument only repeats the type autowiring injects, remove the args() call and let autowiring resolve the constructor.',
                19,
            ],
            [
                'Every argument only repeats the type autowiring injects, remove the args() call and let autowiring resolve the constructor.',
                23,
            ],
            [
                'Argument "$bar" only repeats the type autowiring injects, remove the arg() call and let autowiring resolve "Utils\PHPStan\Tests\Rule\Fixture\AutowiredArgumentBundle\Bar".',
                27,
            ],
        ]);
    }
}
