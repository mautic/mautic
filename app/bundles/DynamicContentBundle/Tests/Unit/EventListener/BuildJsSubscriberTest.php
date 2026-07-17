<?php

declare(strict_types=1);

namespace Mautic\DynamicContentBundle\Tests\Unit\EventListener;

use Mautic\CoreBundle\Event\BuildJsEvent;
use Mautic\CoreBundle\Event\BuildJsScope;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Twig\Helper\AssetsHelper;
use Mautic\DynamicContentBundle\EventListener\BuildJsSubscriber;
use PHPUnit\Framework\Assert;
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

        $requestStack = new RequestStack();
        $requestStack->push(Request::create('https://mautic.example'));

        $router = $this->createStub(RouterInterface::class);
        $router->method('generate')->willReturn('https://mautic.example/dwc/slotNamePlaceholder');

        $this->subscriber = new BuildJsSubscriber($assetsHelper, $translator, $requestStack, $router);
    }

    public function testEssentialBuildRegistersDormantReplacementAndEnhancementHelpers(): void
    {
        $event = new BuildJsEvent('', true, [BuildJsScope::ESSENTIAL]);

        $this->subscriber->onBuildJs($event);

        $js = $event->getJs();
        Assert::assertStringContainsString('MauticJS.replaceDynamicContent = function', $js);
        Assert::assertStringContainsString('MauticJS.enhanceDynamicContent = function', $js);
        Assert::assertStringContainsString('MauticJS.beforeFirstEventDelivery(MauticJS.replaceDynamicContent);', $js);
        Assert::assertStringContainsString('media/js/mautic-form.js', $js);
        Assert::assertStringContainsString('MauticSDK.onLoad();', $js);
        Assert::assertStringContainsString('search("/focus/")', $js);
        Assert::assertStringNotContainsString('MauticJS.setTrackedContact(response)', $js);
        Assert::assertSame(2, substr_count($js, 'MauticJS.replaceDynamicContent'));
    }

    public function testTrackingBuildOnlyAddsIdentityResponseHook(): void
    {
        $event = new BuildJsEvent('', true, [BuildJsScope::TRACKING]);

        $this->subscriber->onBuildJs($event);

        $js = $event->getJs();
        Assert::assertStringContainsString('MauticJS.runtimeReady !== true', $js);
        Assert::assertStringContainsString('MauticJS.onDynamicContentResponse = function(response)', $js);
        Assert::assertStringContainsString('MauticJS.setTrackedContact(response)', $js);
        Assert::assertStringNotContainsString('MauticJS.replaceDynamicContent = function', $js);
        Assert::assertStringNotContainsString('mautic-form.js', $js);
        Assert::assertStringNotContainsString('search("/focus/")', $js);
    }

    public function testLegacyBuildHasOneReplacementAndOneEnhancementPass(): void
    {
        $event = new BuildJsEvent('', true);

        $this->subscriber->onBuildJs($event);

        $js = $event->getJs();
        Assert::assertSame(1, substr_count($js, 'MauticJS.replaceDynamicContent = function'));
        Assert::assertSame(1, substr_count($js, 'MauticJS.beforeFirstEventDelivery(MauticJS.replaceDynamicContent);'));
        Assert::assertSame(1, substr_count($js, "MauticJS.makeCORSRequest('GET', url"));
        Assert::assertSame(1, substr_count($js, 'MauticJS.enhanceDynamicContent(dwcContent);'));
        Assert::assertLessThan(
            strpos($js, 'MauticJS.setTrackedContact(response)'),
            strpos($js, 'MauticJS.replaceDynamicContent = function'),
        );
    }
}
