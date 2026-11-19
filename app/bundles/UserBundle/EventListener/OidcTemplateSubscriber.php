<?php

declare(strict_types=1);

namespace Mautic\UserBundle\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\CustomTemplateEvent;
use Mautic\UserBundle\Security\OIDC\Settings;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class OidcTemplateSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private Settings $settings,
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
        // Inject OIDC settings into login template
        if (in_array($event->getTemplate(), ['@MauticUser/Security/login.html.twig', '@MauticUser/User/form.html.twig', '@MauticUser/FormTheme/Config/_config_userconfig_widget.html.twig'], true)) {
            $vars = $event->getVars();
            $vars['oidcSettings'] = $this->settings;
            $event->setVars($vars);
        }
    }
}
