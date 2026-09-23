<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Twig\Sandbox;

use Mautic\CoreBundle\Twig\Sandbox\ThemeSandboxPolicy;
use Mautic\FormBundle\Entity\Field;
use Mautic\PageBundle\Entity\Page;
use Mautic\UserBundle\Entity\User;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Extension\SandboxExtension;
use Twig\Loader\ArrayLoader;
use Twig\Markup;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Sandbox\SecurityNotAllowedMethodError;
use Twig\Sandbox\SecurityNotAllowedPropertyError;

final class ThemeSandboxPolicyTest extends TestCase
{
    private ThemeSandboxPolicy $policy;

    protected function setUp(): void
    {
        $this->policy = new ThemeSandboxPolicy();
    }

    /**
     * The core of GHSA-h7pm-79gc-8867: any method call on a non-Mautic object
     * must be refused. An exec-capable gadget is never a Mautic entity, so the
     * chain {{ email.getRelated().exec('id') }} can never reach shell_exec().
     * The receiver is checked before the method, so the exec() name still
     * throws even though \DateTime has no such method.
     */
    public function testExecCallOnNonMauticObjectIsBlocked(): void
    {
        $this->expectException(SecurityNotAllowedMethodError::class);
        $this->policy->checkMethodAllowed(new \DateTime(), 'exec');
    }

    public function testAccessorCallOnNonMauticObjectIsBlocked(): void
    {
        // Even a genuine get* accessor is refused when the receiver is not a
        // Mautic object, so a chain cannot pivot through arbitrary classes.
        $this->expectException(SecurityNotAllowedMethodError::class);
        $this->policy->checkMethodAllowed(new \DateTime(), 'getTimestamp');
    }

    public function testMethodCallOnStdClassIsBlocked(): void
    {
        $this->expectException(SecurityNotAllowedMethodError::class);
        $this->policy->checkMethodAllowed(new \stdClass(), 'getName');
    }

    /**
     * Read-only accessors on genuine Mautic entities remain usable so that
     * personalization such as {{ page.title }} (resolved to getTitle) keeps
     * working for custom themes.
     */
    public function testReadOnlyAccessorOnMauticEntityIsAllowed(): void
    {
        $this->expectNotToPerformAssertions();
        $this->policy->checkMethodAllowed(new Page(), 'getTitle');
    }

    public function testMutatorOnMauticEntityIsBlocked(): void
    {
        $this->expectException(SecurityNotAllowedMethodError::class);
        $this->policy->checkMethodAllowed(new Page(), 'setTitle');
    }

    /**
     * Sensitive getters that could leak credentials or perform I/O must be
     * blocked even though they match the accessor pattern.
     */
    public function testGetPasswordIsBlocked(): void
    {
        $this->expectException(SecurityNotAllowedMethodError::class);
        $this->policy->checkMethodAllowed(new User(), 'getPassword');
    }

    public function testGetPasswordIsCaseInsensitive(): void
    {
        $this->expectException(SecurityNotAllowedMethodError::class);
        $this->policy->checkMethodAllowed(new User(), 'GetPassword');
    }

    /**
     * Form template methods are explicitly allowed so custom themes can
     * override form builder templates without breaking.
     */
    public function testFormTemplateMethodsAreAllowed(): void
    {
        $this->expectNotToPerformAssertions();
        $field = new Field();
        $this->policy->checkMethodAllowed($field, 'showForContact');
        $this->policy->checkMethodAllowed($field, 'setType');
        $this->policy->checkMethodAllowed($field, 'setCustomParameters');
    }

    /**
     * An accessor-looking name that only exists via __call must be blocked;
     * method_exists() is false for such names, closing the __call gadget path.
     */
    public function testNonExistentAccessorIsBlocked(): void
    {
        $this->expectException(SecurityNotAllowedMethodError::class);
        $this->policy->checkMethodAllowed(new Page(), 'getSomethingThatDoesNotExist');
    }

    public function testMagicMethodOnMauticEntityIsBlocked(): void
    {
        $this->expectException(SecurityNotAllowedMethodError::class);
        $this->policy->checkMethodAllowed(new Page(), '__toString');
    }

    public function testPropertyAccessOnNonMauticObjectIsBlocked(): void
    {
        $this->expectException(SecurityNotAllowedPropertyError::class);
        $this->policy->checkPropertyAllowed(new \stdClass(), 'anything');
    }

    public function testPropertyAccessOnMauticEntityIsAllowed(): void
    {
        $this->expectNotToPerformAssertions();
        $this->policy->checkPropertyAllowed(new Page(), 'title');
    }

    public function testDangerousFiltersAreBlocked(): void
    {
        $this->expectException(SecurityNotAllowedFilterError::class);
        $this->policy->checkSecurity([], ['map'], []);
    }

    /**
     * The less_compile filter allows Less's @import (inline) directive to read
     * arbitrary files accessible to the PHP process (GHSA-275m-hc5j-qqrg).
     */
    public function testLessCompileFilterIsBlocked(): void
    {
        $this->expectException(SecurityNotAllowedFilterError::class);
        $this->policy->checkSecurity([], ['less_compile'], []);
    }

    public function testDangerousFunctionsAreBlocked(): void
    {
        $this->expectException(SecurityNotAllowedFunctionError::class);
        $this->policy->checkSecurity([], [], ['configGetParameter']);
    }

    public function testLegitimateFunctionsAndFiltersPass(): void
    {
        $this->expectNotToPerformAssertions();
        $this->policy->checkSecurity(['if'], ['upper', 'escape'], ['getAssetUrl', 'constant']);
    }

    /**
     * End-to-end proof through a real sandboxed Twig environment that a method
     * call on a non-Mautic object is refused at render time. This is the
     * mechanism that neutralizes the advisory chain
     * {{ email.getRelated().exec('id') }}: the exec-capable object reached by
     * such a chain is never a Mautic entity, so Twig's sandbox rejects it.
     */
    public function testMethodCallOnNonMauticObjectIsBlockedAtRenderTime(): void
    {
        $twig = new Environment(new ArrayLoader([
            'exploit' => '{{ clock.getTimestamp() }}',
        ]));
        $twig->addExtension(new SandboxExtension($this->policy, true));

        $this->expectException(SecurityNotAllowedMethodError::class);
        $twig->render('exploit', ['clock' => new \DateTime()]);
    }

    /**
     * Captured blocks ({% set x %}...{% endset %}) produce Twig\Markup objects.
     * Printing them or passing them through filters (e.g. json_encode) makes
     * Twig's sandbox check __toString. Markup is a harmless string wrapper, so
     * this must be allowed or every theme using captured blocks breaks.
     */
    public function testMarkupToStringIsAllowed(): void
    {
        $this->expectNotToPerformAssertions();
        $this->policy->checkMethodAllowed(new Markup('<p>hi</p>', 'UTF-8'), '__toString');
    }

    /**
     * End-to-end proof that the beefree themes' base.html.twig pattern —
     * captured {% set %} blocks serialized via json_encode — renders under
     * the sandbox without throwing SecurityNotAllowedMethodError.
     */
    public function testCapturedBlocksThroughJsonEncodeRenderAtRenderTime(): void
    {
        $twig = new Environment(new ArrayLoader([
            'theme' => <<<'TWIG'
{% set html %}
    <p>hello</p>
{% endset %}
{% set rendered %}
    <p>world</p>
{% endset %}
{% set payload = {html: html, rendered: rendered} %}
{{ payload|json_encode|raw }}
TWIG,
        ]));
        $twig->addExtension(new SandboxExtension($this->policy, true));

        $output = $twig->render('theme');
        $this->assertStringContainsString('hello', $output);
        $this->assertStringContainsString('world', $output);
    }
}
