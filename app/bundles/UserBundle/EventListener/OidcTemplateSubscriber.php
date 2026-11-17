<?php

declare(strict_types=1);

namespace Mautic\UserBundle\EventListener;

use Mautic\CoreBundle\Event\CustomTemplateEvent;
use Mautic\UserBundle\Security\OIDC\Settings;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final readonly class OidcTemplateSubscriber
{
    public function __construct(
        private Settings $settings,
    ) {
    }

    #[AsEventListener(
        event: CustomTemplateEvent::class,
    )]
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
