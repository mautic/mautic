<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Security\OIDC\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\CustomTemplateEvent;
use Mautic\UserBundle\Security\OIDC\Settings;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Twig\Environment;

final class InjectCustomTemplateSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly Settings $settings,
        private readonly Environment $twig,
    ) {
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
        if ('@MauticUser/FormTheme/Config/_config_userconfig_widget.html.twig' === $event->getTemplate()) {
            $event->appendContent($this->twig->render('@MauticUser/Security/OIDC/oidc_config.html.twig', [
                'form' => $event->getVars()['form'],
            ]));
        }
    }

    private function addLoginButton(CustomTemplateEvent $event): void
    {
        if ('@MauticUser/Security/login.html.twig' === $event->getTemplate()) {
            $event->prependContent($this->twig->render('@MauticUser/Security/OIDC/oidc_login_top.html.twig', [
                'parameters' => $this->settings,
            ]));
            $event->appendContent($this->twig->render('@MauticUser/Security/OIDC/oidc_login_bottom.html.twig', [
                'parameters' => $this->settings,
            ]));
        }
    }

    private function addUserFields(CustomTemplateEvent $event): void
    {
        if ('@MauticUser/User/form.html.twig' === $event->getTemplate()) {
            $event->appendContent($this->twig->render('@MauticUser/Security/OIDC/oidc_user_form.html.twig', [
                'form' => $event->getVars()['form'],
            ]));
        }
    }
}
