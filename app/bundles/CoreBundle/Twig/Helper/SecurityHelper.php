<?php

namespace Mautic\CoreBundle\Twig\Helper;

use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Event\AuthenticationContentEvent;
use Mautic\UserBundle\UserEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * final class SecurityHelper.
 */
final class SecurityHelper
{
    /**
     * SecurityHelper constructor.
     */
    public function __construct(private CorePermissions $security, private RequestStack $requestStack, private EventDispatcherInterface $dispatcher, private CsrfTokenManagerInterface $tokenManager)
    {
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'security';
    }

    /**
     * Helper function to check if the logged in user has access to an entity.
     */
    public function hasEntityAccess(string|bool $ownPermission, string|bool $otherPermission, User|int $ownerId): bool
    {
        return $this->security->hasEntityAccess($ownPermission, $otherPermission, $ownerId);
    }

    /**
     * @param string[]|string $permission
     *
     * @return mixed
     */
    public function isGranted(array|string $permission)
    {
        return $this->security->isGranted($permission);
    }

    /**
     * Get content from listeners.
     */
    public function getAuthenticationContent(): string
    {
        $request = $this->requestStack->getCurrentRequest();
        $content = '';
        if ($this->dispatcher->hasListeners(UserEvents::USER_AUTHENTICATION_CONTENT)) {
            $event = new AuthenticationContentEvent($request);
            $this->dispatcher->dispatch($event, UserEvents::USER_AUTHENTICATION_CONTENT);
            $content = $event->getContent();

            // Remove post_logout session after content has been generated
            $request->getSession()->remove('post_logout');
        }

        return $content;
    }

    /**
     * Returns CSRF token string for an intention.
     *
     * @param string $intention
     *
     * @return string
     */
    public function getCsrfToken($intention)
    {
        return $this->tokenManager->getToken($intention)->getValue();
    }
}
