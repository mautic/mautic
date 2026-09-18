<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Security\OIDC\Unit\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\CustomTemplateEvent;
use Mautic\CoreBundle\Templating\Helper\SlotsHelper;
use Mautic\UserBundle\Security\OIDC\EventListener\InjectCustomTemplateSubscriber;
use Mautic\UserBundle\Security\OIDC\Tests\Builder\DTO\ParametersBuilder;
use PHPStan\Testing\TestCase;
use Symfony\Component\Templating\PhpEngine;
use Twig\Environment;

final class InjectCustomTemplateSubscriberTest extends TestCase
{
    public function testGetSubscribedEvents(): void
    {
        self::assertEquals([CoreEvents::VIEW_INJECT_CUSTOM_TEMPLATE => ['onTemplateRender', 0]], InjectCustomTemplateSubscriber::getSubscribedEvents());
    }

    // we always want to inject the configuration to the template
    // so the user can enable/disable the feature in the UI
    public function testOnTemplateRenderInjectsConfigEvenWhenOpenIdIsDisabled(): void
    {
        $parameters          = (new ParametersBuilder())->withIsEnabled(false)->build();
        $slotsHelper         = self::createMock(SlotsHelper::class);
        $twig                = self::createMock(Environment::class);
        $phpEngine           = self::createMock(PhpEngine::class);
        $customTemplateEvent = self::createMock(CustomTemplateEvent::class);

        $customTemplateEvent->expects(self::once())
            ->method('getTemplate')
            ->willReturn('MauticUserBundle:FormTheme\Config:_config_userconfig_widget.html.php');

        $slotsHelper->expects(self::once())
            ->method('set')
            ->with('appended_user_config_fields', $phpEngine->render('OpenIdBundle:Form:config.html.php', [
                'form' => $customTemplateEvent->getVars()['form'],
            ]));

        $injectCustomTemplateSubscriber = new InjectCustomTemplateSubscriber($parameters, $slotsHelper, $twig, $phpEngine);
        $injectCustomTemplateSubscriber->onTemplateRender($customTemplateEvent);
    }

    public function testOnTemplateRenderInjectsButtonOnLoginPage(): void
    {
        $parameters          = (new ParametersBuilder())->build();
        $slotsHelper         = self::createMock(SlotsHelper::class);
        $twig                = self::createMock(Environment::class);
        $phpEngine           = self::createMock(PhpEngine::class);
        $customTemplateEvent = self::createMock(CustomTemplateEvent::class);

        $customTemplateEvent->expects(self::atLeastOnce())
            ->method('getTemplate')
            ->willReturn('MauticUserBundle:Security:login.html.php');

        $slotsHelper->expects(self::atLeastOnce())
            ->method('set')
            ->withConsecutive(['above_login_form'], ['below_login_form']);

        $injectCustomTemplateSubscriber = new InjectCustomTemplateSubscriber($parameters, $slotsHelper, $twig, $phpEngine);
        $injectCustomTemplateSubscriber->onTemplateRender($customTemplateEvent);
    }

    public function testOnTemplateRenderInjectsUserFieldsOnUserEditPage(): void
    {
        $parameters          = (new ParametersBuilder())->build();
        $slotsHelper         = self::createMock(SlotsHelper::class);
        $twig                = self::createMock(Environment::class);
        $phpEngine           = self::createMock(PhpEngine::class);
        $customTemplateEvent = self::createMock(CustomTemplateEvent::class);

        $customTemplateEvent->expects(self::atLeastOnce())
            ->method('getTemplate')
            ->willReturn('MauticUserBundle:User:form.html.php');

        $slotsHelper->expects(self::atLeastOnce())
            ->method('set')
            ->with('appended_user_fields', $phpEngine->render('OpenIdBundle:Form:user.html.php', [
                'form' => $customTemplateEvent->getVars()['form'],
            ]));

        $injectCustomTemplateSubscriber = new InjectCustomTemplateSubscriber($parameters, $slotsHelper, $twig, $phpEngine);
        $injectCustomTemplateSubscriber->onTemplateRender($customTemplateEvent);
    }
}
