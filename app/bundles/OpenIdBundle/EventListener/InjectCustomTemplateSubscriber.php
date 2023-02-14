<?php

declare(strict_types=1);

namespace Mautic\OpenIdBundle\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\CustomTemplateEvent;
use Mautic\CoreBundle\Templating\Helper\SlotsHelper;
use Mautic\OpenIdBundle\DTO\Settings;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Templating\PhpEngine;
use Twig\Environment;

final class InjectCustomTemplateSubscriber implements EventSubscriberInterface
{
    private Settings $settings;
    private SlotsHelper $slotsHelper;
    private Environment $twig;
    private PhpEngine $phpEngine;

    public function __construct(
        Settings $settings,
        SlotsHelper $slotsHelper,
        Environment $twig,
        PhpEngine $phpEngine
    )
    {
        $this->settings    = $settings;
        $this->slotsHelper = $slotsHelper;
        $this->twig        = $twig;
        $this->phpEngine   = $phpEngine;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CoreEvents::VIEW_INJECT_CUSTOM_TEMPLATE => ['onTemplateRender', 0],
        ];
    }

    public function onTemplateRender(CustomTemplateEvent $event): void
    {
        // we always want to inject the configuration to the template
        // so the user can enable/disable the feature in the UI
        $this->addConfig($event);

        if (!$this->settings->isEnabled()) {
            return;
        }

        $this->addLoginButton($event);
        $this->addUserFields($event);
    }

    private function addConfig(CustomTemplateEvent $event): void
    {
        if ('MauticUserBundle:FormTheme\Config:_config_userconfig_widget.html.php' === $event->getTemplate()) {
            $this->slotsHelper->set('appended_user_config_fields', $this->phpEngine->render('OpenIdBundle:Form:config.html.php', [
                'form' => $event->getVars()['form'],
            ]));
        }
    }

    private function addLoginButton(CustomTemplateEvent $event): void
    {
        if ('MauticUserBundle:Security:login.html.php' === $event->getTemplate()) {
            $this->slotsHelper->set('above_login_form', $this->twig->render('OpenIdBundle:security:login_top.html.twig', [
                'parameters' => $this->settings,
            ]));
            $this->slotsHelper->set('below_login_form', $this->twig->render('OpenIdBundle:security:login_bottom.html.twig', [
                'parameters' => $this->settings,
            ]));
        }
    }

    private function addUserFields(CustomTemplateEvent $event): void
    {
        if ('MauticUserBundle:User:form.html.php' === $event->getTemplate()) {
            $this->slotsHelper->set('appended_user_fields', $this->phpEngine->render('OpenIdBundle:Form:user.html.php', [
                'form' => $event->getVars()['form'],
            ]));
        }
    }
}
