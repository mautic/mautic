<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Security\OIDC\EventListener;

use Mautic\UserBundle\Security\OIDC\Event\RegisterScopesEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class RegisterScopesSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            RegisterScopesEvent::class => ['registerScopes', 0],
        ];
    }

    public function registerScopes(RegisterScopesEvent $event): void
    {
        $event->addScopes(['openid', 'email', 'profile']);
    }
}
