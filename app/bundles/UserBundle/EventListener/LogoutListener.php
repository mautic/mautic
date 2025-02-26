<?php

namespace Mautic\UserBundle\EventListener;

use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\UserBundle\Event\LogoutEvent;
use Mautic\UserBundle\Model\UserModel;
use Mautic\UserBundle\UserEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class LogoutListener implements \Symfony\Component\EventDispatcher\EventSubscriberInterface
{
    protected ?\Mautic\UserBundle\Entity\User $user;

    public function __construct(
        protected UserModel $userModel,
        protected EventDispatcherInterface $dispatcher,
        UserHelper $userHelper,
    ) {
        $this->user       = $userHelper->getUser();
    }

    public function onLogout(\Symfony\Component\Security\Http\Event\LogoutEvent $logoutEvent): void
    {
        $request = $logoutEvent->getRequest();
        $session = $request->getSession();
        if ($this->dispatcher->hasListeners(UserEvents::USER_LOGOUT)) {
            $mauticEvent = new LogoutEvent($this->user, $request);
            $this->dispatcher->dispatch($mauticEvent, UserEvents::USER_LOGOUT);
            $sessionItems = $mauticEvent->getPostSessionItems();
            foreach ($sessionItems as $key => $value) {
                $session->set($key, $value);
            }
        }
        // Clear session
        $session->clear();

        // Note that a logout occurred
        $session->set('post_logout', true);
    }

    /**
     * @return array<string, mixed>
     */
    public static function getSubscribedEvents(): array
    {
        return [\Symfony\Component\Security\Http\Event\LogoutEvent::class => 'onLogout'];
    }

    public function onSymfonyComponentSecurityHttpEventLogoutEvent(\Symfony\Component\Security\Http\Event\LogoutEvent $logoutEvent): void
    {
        $this->onLogout($logoutEvent);
    }
}
