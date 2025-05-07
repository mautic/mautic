<?php

declare(strict_types=1);

namespace Mautic\CacheBundle\EventListener;

use Mautic\UserBundle\Event\LogoutEvent;
use Mautic\UserBundle\UserEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Csrf\TokenStorage\ClearableTokenStorageInterface;

class UserSubscriber implements EventSubscriberInterface
{
    private ClearableTokenStorageInterface $tokenStorage;

    public function __construct(ClearableTokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            UserEvents::USER_LOGOUT => 'onUserLogout',
        ];
    }

    public function onUserLogout(LogoutEvent $event): void
    {
        $this->tokenStorage->clear();
    }
}
