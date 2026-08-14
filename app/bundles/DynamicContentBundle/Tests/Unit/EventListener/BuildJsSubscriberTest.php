<?php

declare(strict_types=1);

namespace Mautic\DynamicContentBundle\Tests\Unit\EventListener;

use Mautic\CoreBundle\Event\BuildJsEvent;
use Mautic\CoreBundle\Event\BuildJsScope;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Twig\Helper\AssetsHelper;
use Mautic\DynamicContentBundle\EventListener\BuildJsSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class BuildJsSubscriberTest extends TestCase
{
    private BuildJsSubscriber $subscriber;

    protected function setUp(): void
    {
        $pathsHelper = $this->createStub(PathsHelper::class);
        $pathsHelper->method('getSystemPath')->willReturn('');

        $assetsHelper = new AssetsHelper($this->createStub(Packages::class));
        $assetsHelper->setPathsHelper($pathsHelper);
        $assetsHelper->setSiteUrl('https://mautic.example');

        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturn('Please wait');

        $requestStack = new RequestStack([Request::create('https://mautic.example')]);

        $router = $this->createStub(RouterInterface::class);
        $router->method('generate')->willReturn('https://mautic.example/dwc/slotNamePlaceholder');

        $this->subscriber = new BuildJsSubscriber($assetsHelper, $translator, $requestStack, $router);
    }

    public function testEssentialBuildRegistersDormantReplacementAndEnhancementHelpers(): void
    {
        $event = new BuildJsEvent('', true, [BuildJsScope::ESSENTIAL]);

        $this->subscriber->onBuildJs($event);

        $js = $event->getJs();
        $this->assertStringContainsString('MauticJS.replaceDynamicContent = function', $js);
        $this->assertStringContainsString('MauticJS.enhanceDynamicContent = function', $js);
        $this->assertStringContainsString('MauticJS.initializeForms = function', $js);
        $this->assertStringContainsString("document.querySelectorAll('.mautic-slot form[data-mautic-form]", $js);
        $this->assertStringContainsString("document.getElementById('mauticform_' + formId + '_messenger')", $js);
        $this->assertStringContainsString('MauticJS.beforeFirstEventDelivery(MauticJS.replaceDynamicContent);', $js);
        $this->assertStringContainsString('media/js/mautic-form.js', $js);
        $this->assertStringContainsString("typeof MauticSDKLoaded == 'undefined'", $js);
        $this->assertStringContainsString('MauticSDK.onLoad();', $js);
        $this->assertStringContainsString('search("/focus/")', $js);
        $this->assertStringNotContainsString('MauticJS.setTrackedContact(response)', $js);
        $this->assertSame(2, substr_count($js, 'MauticJS.replaceDynamicContent'));
    }

    public function testTrackingBuildOnlyAddsIdentityResponseHook(): void
    {
        $event = new BuildJsEvent('', true, [BuildJsScope::TRACKING]);

        $this->subscriber->onBuildJs($event);

        $js = $event->getJs();
        $this->assertStringContainsString('MauticJS.runtimeReady !== true', $js);
        $this->assertStringContainsString('MauticJS.onDynamicContentResponse = function(response)', $js);
        $this->assertStringContainsString('MauticJS.setTrackedContact(response)', $js);
        $this->assertStringNotContainsString('MauticJS.replaceDynamicContent = function', $js);
        $this->assertStringNotContainsString('mautic-form.js', $js);
        $this->assertStringNotContainsString('search("/focus/")', $js);
    }

    public function testLegacyBuildHasOneReplacementAndOneEnhancementPass(): void
    {
        $event = new BuildJsEvent('', true);

        $this->subscriber->onBuildJs($event);

        $js = $event->getJs();
        $this->assertSame(1, substr_count($js, 'MauticJS.replaceDynamicContent = function'));
        $this->assertSame(1, substr_count($js, 'MauticJS.beforeFirstEventDelivery(MauticJS.replaceDynamicContent);'));
        $this->assertSame(1, substr_count($js, "MauticJS.makeCORSRequest('GET', url"));
        $this->assertSame(1, substr_count($js, 'MauticJS.enhanceDynamicContent(dwcContent);'));
        $this->assertLessThan(strpos($js, 'MauticJS.setTrackedContact(response)'), strpos($js, 'MauticJS.replaceDynamicContent = function'));
    }
}
