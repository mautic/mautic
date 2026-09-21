<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Utils\PHPStan\Rule\NoServiceInMethodParameterRule;

/**
 * @extends RuleTestCase<NoServiceInMethodParameterRule>
 */
final class NoServiceInMethodParameterRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NoServiceInMethodParameterRule($this->createReflectionProvider());
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__.'/Fixture/ServiceInMethodParameter.php'], [
            [
                'Service "$formFactory" of type "Symfony\Component\Form\FormFactoryInterface" is passed to method "buildForm()". Inject it in the constructor or an autowire*() method instead.',
                16,
            ],
            [
                'Service "$translator" of type "Symfony\Contracts\Translation\TranslatorInterface" is passed to method "translate()". Inject it in the constructor or an autowire*() method instead.',
                21,
            ],
        ]);
    }

    public function testSkipConstructorAutowireAndRequiredMethod(): void
    {
        $this->analyse([__DIR__.'/Fixture/ServiceInInjectionMethod.php'], []);
    }

    public function testSkipCreateFormInChildFormModel(): void
    {
        $this->analyse([__DIR__.'/Fixture/CreateFormInChildFormModel.php'], []);
    }

    public function testSkipControllerAction(): void
    {
        $this->analyse([__DIR__.'/Fixture/ServiceInControllerActionController.php'], []);
    }

    public function testSkipStaticMethod(): void
    {
        $this->analyse([__DIR__.'/Fixture/ServiceInStaticMethod.php'], []);
    }

    public function testSkipCreateMethod(): void
    {
        $this->analyse([__DIR__.'/Fixture/ServiceInCreateMethod.php'], []);
    }

    public function testSkipFosAuthorizeController(): void
    {
        $this->analyse([__DIR__.'/Fixture/ServiceInFosAuthorizeController.php'], []);
    }

    public function testSkipTwigExtensionClass(): void
    {
        $this->analyse([__DIR__.'/Fixture/ServiceInTwigExtensionMethod.php'], []);
    }

    public function testSkipEntityClass(): void
    {
        $this->analyse([__DIR__.'/Fixture/ServiceInEntityMethod.php'], []);
    }

    public function testSkipEventClass(): void
    {
        $this->analyse([__DIR__.'/Fixture/ServiceInEventMethod.php'], []);
    }

    public function testSkipTraitMethod(): void
    {
        $this->analyse([__DIR__.'/Fixture/ServiceInTraitMethod.php'], []);
    }

    public function testSkipTestCaseMethod(): void
    {
        $this->analyse([__DIR__.'/Fixture/ServiceInTestCaseMethod.php'], []);
    }

    public function testSkipNonServiceParameter(): void
    {
        $this->analyse([__DIR__.'/Fixture/NonServiceInMethodParameter.php'], []);
    }
}
