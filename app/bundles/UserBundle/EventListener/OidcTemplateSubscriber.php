<?php

declare(strict_types=1);

namespace Mautic\UserBundle\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\CustomTemplateEvent;
use Mautic\UserBundle\Security\OIDC\Settings;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class OidcTemplateSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly Settings $settings,
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
        $template = $event->getTemplate();

        // Inject OIDC settings into login template
        if ('@MauticUser/Security/login.html.twig' === $template) {
            $vars = $event->getVars();
            $vars['oidcSettings'] = $this->settings;
            $event->setVars($vars);
        }

        // Inject OIDC settings into user form template
        if ('@MauticUser/User/form.html.twig' === $template) {
            $vars = $event->getVars();
            $vars['oidcSettings'] = $this->settings;
            $event->setVars($vars);
        }

        // Inject OIDC settings into config widget template
        if ('@MauticUser/FormTheme/Config/_config_userconfig_widget.html.twig' === $template) {
            $vars = $event->getVars();
            $vars['oidcSettings'] = $this->settings;
            $event->setVars($vars);
        }
    }
}
