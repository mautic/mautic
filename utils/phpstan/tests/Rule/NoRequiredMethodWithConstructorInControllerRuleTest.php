<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Utils\PHPStan\Rule\NoRequiredMethodWithConstructorInControllerRule;

/**
 * @extends RuleTestCase<NoRequiredMethodWithConstructorInControllerRule>
 */
final class NoRequiredMethodWithConstructorInControllerRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NoRequiredMethodWithConstructorInControllerRule();
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__.'/Fixture/ConstructorAndAutowireController.php'], [
            [
                'Method "autowireConstructorAndAutowireController()" has the #[Required] attribute, but "ConstructorAndAutowireController" defines its own constructor. Move the dependency to the constructor and drop the method.',
                16,
            ],
        ]);

        // the setter alone is the way to inject into an inherited constructor
        $this->analyse([__DIR__.'/Fixture/SetterAutowireController.php'], []);

        $this->analyse([__DIR__.'/Fixture/CommonConstructorAndAutowireController.php'], []);

        $this->analyse([__DIR__.'/Fixture/AbstractConstructorAndAutowireController.php'], []);
    }
}
