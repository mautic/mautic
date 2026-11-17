<?php

declare(strict_types=1);

namespace Mautic\UserBundle\EventListener;

use Mautic\UserBundle\Security\OIDC\RegisterScopesEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final class OidcScopesSubscriber
{
    #[AsEventListener(event: RegisterScopesEvent::class)]
    public function registerScopes(RegisterScopesEvent $event): void
    {
        $event->addScopes(['openid', 'email', 'profile']);
    }
}
