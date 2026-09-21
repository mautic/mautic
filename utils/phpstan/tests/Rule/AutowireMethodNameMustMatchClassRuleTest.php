<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Utils\PHPStan\Rule\AutowireMethodNameMustMatchClassRule;

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
                11,
            ],
        ]);
    }

    public function testSkipCorrectlyNamedMethod(): void
    {
        $this->analyse([__DIR__.'/Fixture/NamedAutowireController.php'], []);
    }

    public function testAjaxControllerWithoutBundleName(): void
    {
        $this->analyse([__DIR__.'/Fixture/SomeBundle/AjaxController.php'], [
            [
                'Method "autowireAjaxController()" has the #[Required] attribute, so it must be named "autowireSomeAjaxController()". Rename it to keep the autowired method unique in the class hierarchy.',
                12,
            ],
        ]);
    }

    public function testSkipAjaxControllerWithBundleName(): void
    {
        $this->analyse([__DIR__.'/Fixture/OtherBundle/AjaxController.php'], []);
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
