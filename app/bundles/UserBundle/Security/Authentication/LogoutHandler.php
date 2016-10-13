<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Security\Authentication;

use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\UserBundle\Event\LogoutEvent;
use Mautic\UserBundle\Model\UserModel;
use Mautic\UserBundle\UserEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Logout\LogoutHandlerInterface;

class LogoutHandler implements LogoutHandlerInterface
{
    /**
     * @var UserModel
     */
    protected $userModel;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @var \Mautic\UserBundle\Entity\User|null
     */
    protected $user;

    /**
     * LogoutHandler constructor.
     *
     * @param UserModel                $userModel
     * @param EventDispatcherInterface $dispatcher
     * @param UserHelper               $userHelper
     */
    public function __construct(UserModel $userModel, EventDispatcherInterface $dispatcher, UserHelper $userHelper)
    {
        $this->userModel  = $userModel;
        $this->dispatcher = $dispatcher;
        $this->user       = $userHelper->getUser();
    }

    /**
     * {@inheritdoc}
     *
     * @param Request $request
     *
     * @return Response never null
     */
    public function logout(Request $request, Response $response, TokenInterface $token)
    {
        $this->userModel->setOnlineStatus('offline');

        if ($this->dispatcher->hasListeners(UserEvents::USER_LOGOUT)) {
            $event = new LogoutEvent($this->user, $request);
            $this->dispatcher->dispatch(UserEvents::USER_LOGOUT, $event);
        }

        // Clear session
        $session = $request->getSession();
        $session->clear();

        if (isset($event)) {
            $sessionItems = $event->getPostSessionItems();
            foreach ($sessionItems as $key => $value) {
                $session->set($key, $value);
            }
        }
        // Note that a logout occurred
        $session->set('post_logout', true);
    }
}
