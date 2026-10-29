<?php

namespace Mautic\UserBundle\EventListener;

use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Event\LogoutEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

<<<<<<< HEAD
final readonly class LogoutListener implements EventSubscriberInterface
{
    private ?User $user;

=======
final readonly class LogoutListener
{
    private ?\Mautic\UserBundle\Entity\User $user;
>>>>>>> 9d2fcd5904 (rebase)
    public function __construct(
        private EventDispatcherInterface $dispatcher,
        UserHelper $userHelper,
    ) {
        $this->user       = $userHelper->getUser();
    }
    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(event: \Symfony\Component\Security\Http\Event\LogoutEvent::class)]
    public function onLogout(\Symfony\Component\Security\Http\Event\LogoutEvent $logoutEvent): void
    {
        $request = $logoutEvent->getRequest();
        $session = $request->getSession();
        if ($this->dispatcher->hasListeners(LogoutEvent::class)) {
            $mauticEvent = new LogoutEvent($this->user, $request);
            $this->dispatcher->dispatch($mauticEvent);
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
    public function onSymfonyComponentSecurityHttpEventLogoutEvent(\Symfony\Component\Security\Http\Event\LogoutEvent $logoutEvent): void
    {
        $this->onLogout($logoutEvent);
    }
}
