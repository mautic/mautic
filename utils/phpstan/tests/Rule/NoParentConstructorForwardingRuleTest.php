<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Utils\PHPStan\Rule\NoParentConstructorForwardingRule;

/**
 * @extends RuleTestCase<NoParentConstructorForwardingRule>
 */
final class NoParentConstructorForwardingRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NoParentConstructorForwardingRule();
    }

    public function testRule(): void
    {
        $this->analyse([
            __DIR__.'/Fixture/ParentConstructor/ParentService.php',
            __DIR__.'/Fixture/ParentConstructor/ForwardingChildService.php',
            __DIR__.'/Fixture/ParentConstructor/ShortForwardingChildService.php',
            __DIR__.'/Fixture/ParentConstructor/OwnConstructorChildService.php',
            __DIR__.'/Fixture/ParentConstructor/PromotedChildService.php',
            __DIR__.'/Fixture/ParentConstructor/HalfOwnChildService.php',
            __DIR__.'/Fixture/ParentConstructor/ManyOwnChildService.php',
        ], [
            [
                'Constructor hands 10 parameter(s) straight over to parent::__construct(), drop them and take the dependency of its own in an autowire*() method instead.',
                9,
            ],
            [
                'Constructor hands 10 parameter(s) straight over to parent::__construct(), drop them and take the dependency of its own in an autowire*() method instead.',
                9,
            ],
        ]);
    }
}
