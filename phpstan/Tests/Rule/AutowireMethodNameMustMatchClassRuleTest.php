<?php

declare(strict_types=1);

namespace MauticPhpStan\Tests\Rule;

use MauticPhpStan\Rule\AutowireMethodNameMustMatchClassRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<AutowireMethodNameMustMatchClassRule>
 */
final class AutowireMethodNameMustMatchClassRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new AutowireMethodNameMustMatchClassRule();
    }

    public function testPlainAutowireName(): void
    {
        $this->analyse([__DIR__.'/Fixture/CollidingAutowireController.php'], [
            [
                'Method "autowire()" has the #[Required] attribute, so it must be named "autowireCollidingAutowireController()". Rename it to keep the autowired method unique in the class hierarchy.',
                11,
            ],
        ]);
    }

    public function testSetterName(): void
    {
        $this->analyse([__DIR__.'/Fixture/SetterAutowireController.php'], [
            [
                'Method "setSomeModel()" has the #[Required] attribute, so it must be named "autowireSetterAutowireController()". Rename it to keep the autowired method unique in the class hierarchy.',
                9,
            ],
        ]);
    }

    public function testSkipCorrectlyNamedMethod(): void
    {
        $this->analyse([__DIR__.'/Fixture/NamedAutowireController.php'], []);
    }

    public function testSkipMethodWithoutRequiredAttribute(): void
    {
        $this->analyse([__DIR__.'/Fixture/PlainSetterController.php'], []);
    }

    public function testSkipClassOutsideController(): void
    {
        $this->analyse([__DIR__.'/Fixture/SomeAutowireService.php'], []);
    }

    /**
     * The trait declares the method, so its own file decides. A controller using it does not drag it into the check.
     */
    public function testSkipTraitUsedByController(): void
    {
        $this->analyse([__DIR__.'/Fixture/SomeAutowireTrait.php', __DIR__.'/Fixture/TraitUsingController.php'], []);
    }
}
