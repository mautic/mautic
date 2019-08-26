<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Templating\Helper;

use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\UserBundle\Event\AuthenticationContentEvent;
use Mautic\UserBundle\UserEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Templating\Helper\Helper;

/**
 * Class SecurityHelper.
 */
class SecurityHelper extends Helper
{
    /**
     * @var CorePermissions
     */
    private $security;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Dispatcher
     */
    private $dispatcher;

    /**
     * @var CsrfTokenManagerInterface
     */
    private $tokenManager;

    /**
     * SecurityHelper constructor.
     *
     * @param CorePermissions           $security
     * @param RequestStack              $requestStack
     * @param EventDispatcherInterface  $dispatcher
     * @param CsrfTokenManagerInterface $tokenManager
     */
    public function __construct(
        CorePermissions $security,
        RequestStack $requestStack,
        EventDispatcherInterface $dispatcher,
        CsrfTokenManagerInterface $tokenManager
    ) {
        $this->security     = $security;
        $this->request      = $requestStack->getCurrentRequest();
        $this->dispatcher   = $dispatcher;
        $this->tokenManager = $tokenManager;
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
     *
     * @param $ownPermission
     * @param $otherPermission
     * @param $ownerId
     *
     * @return bool
     */
    public function hasEntityAccess($ownPermission, $otherPermission, $ownerId)
    {
        return $this->security->hasEntityAccess($ownPermission, $otherPermission, $ownerId);
    }

    /**
     * @param $permission
     *
     * @return mixed
     */
    public function isGranted($permission)
    {
        return $this->security->isGranted($permission);
    }

    /**
     * Get content from listeners.
     */
    public function getAuthenticationContent()
    {
        $content = '';
        if ($this->dispatcher->hasListeners(UserEvents::USER_AUTHENTICATION_CONTENT)) {
            $event = new AuthenticationContentEvent($this->request);
            $this->dispatcher->dispatch(UserEvents::USER_AUTHENTICATION_CONTENT, $event);
            $content = $event->getContent();

            // Remove post_logout session after content has been generated
            $this->request->getSession()->remove('post_logout');
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
