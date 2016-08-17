<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\EventListener;

use Mautic\CoreBundle\Controller\MauticController;
use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\MenuEvent;
use Mautic\CoreBundle\Event\RouteEvent;
use Mautic\CoreBundle\Event\IconEvent;
use Mautic\ApiBundle\Event as ApiEvents;
use Mautic\InstallBundle\Controller\InstallController;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Event\LoginEvent;
use Mautic\UserBundle\UserEvents;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

/**
 * Class CoreSubscriber
 */
class CoreSubscriber extends CommonSubscriber
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::CONTROLLER          => array('onKernelController', 0),
            KernelEvents::REQUEST             => [
                ['onKernelRequest', 0],
                ['onKernelRequestAddGlobalJS', 0]
            ],
            CoreEvents::BUILD_MENU            => array('onBuildMenu', 9999),
            CoreEvents::BUILD_ROUTE           => array('onBuildRoute', 0),
            CoreEvents::FETCH_ICONS           => array('onFetchIcons', 9999),
            SecurityEvents::INTERACTIVE_LOGIN => array('onSecurityInteractiveLogin', 0)
        );
    }

    /**
     * Set default timezone/locale
     *
     * @param GetResponseEvent $event
     *
     * @return void
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        // Set the user's default locale
        $request = $event->getRequest();
        if (!$request->hasPreviousSession()) {
            return;
        }

        $currentUser = $this->factory->getUser();

        //set the user's timezone
        if (is_object($currentUser)) {
            $tz = $currentUser->getTimezone();
        }

        if (empty($tz)) {
            $tz = $this->params['default_timezone'];
        }

        date_default_timezone_set($tz);

        if (!$locale = $request->attributes->get('_locale')) {
            if (is_object($currentUser)) {
                $locale = $currentUser->getLocale();
            }
            if (empty($locale)) {
                $locale = $this->params['locale'];
            }
        }

        $request->setLocale($locale);

        // Set a cookie with session name for filemanager
        $sessionName = $request->cookies->get('mautic_session_name');
        if ($sessionName != session_name()) {
            /** @var \Mautic\CoreBundle\Helper\CookieHelper $cookieHelper */
            $cookieHelper = $this->factory->getHelper('cookie');
            $cookieHelper->setCookie('mautic_session_name', session_name(), null);
        }
    }

    /**
     * Add mauticForms in js script tag for Froala
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequestAddGlobalJS(GetResponseEvent $event)
    {
        if (defined('MAUTIC_INSTALLER')) {
            return;
        }

        $list = $this->factory->getEntityManager()->getRepository('MauticFormBundle:Form')->getSimpleList();

        $mauticForms = json_encode($list, JSON_FORCE_OBJECT | JSON_PRETTY_PRINT);

        $this->factory->getHelper('template.assets')->addScriptDeclaration("var mauticForms = {$mauticForms};");
    }

    /**
     * Set vars on login
     *
     * @param InteractiveLoginEvent $event
     *
     * @return void
     */
    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event)
    {
        if (defined('MAUTIC_INSTALLER')) {
            return;
        }

        $session = $event->getRequest()->getSession();
        $securityContext = $this->factory->getSecurityContext();
        if ($securityContext->isGranted('IS_AUTHENTICATED_FULLY') || $securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {

            $user = $event->getAuthenticationToken()->getUser();

            //set a session var for filemanager to know someone is logged in
            $session->set('mautic.user', $user->getId());

            //mark the user as last logged in
            $user = $this->factory->getUser();
            if ($user instanceof User) {
                /** @var \Mautic\UserBundle\Model\UserModel $userModel */
                $userModel = $this->factory->getModel('user');
                $userModel->setOnlineStatus('online');

                $userModel->getRepository()->setLastLogin($user);
            }

            //dispatch on login events
            $dispatcher = $this->factory->getDispatcher();
            if ($dispatcher->hasListeners(UserEvents::USER_LOGIN)) {
                $event = new LoginEvent($this->factory->getUser());
                $dispatcher->dispatch(UserEvents::USER_LOGIN, $event);
            }
        } else {
            $session->remove('mautic.user');
        }

        //set a couple variables used by filemanager
        $session->set('mautic.docroot', $event->getRequest()->server->get('DOCUMENT_ROOT'));
        $session->set('mautic.basepath', $event->getRequest()->getBasePath());
        $session->set('mautic.imagepath', $this->factory->getParameter('image_path'));
    }

    /**
     * Populates namespace, bundle, controller, and action into request to be used throughout application
     *
     * @param FilterControllerEvent $event
     *
     * @return void
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        $controller = $event->getController();

        if (!is_array($controller)) {
            return;
        }

        //only affect Mautic controllers
        if ($controller[0] instanceof MauticController) {
            $request = $event->getRequest();

            //also set the request for easy access throughout controllers
            $controller[0]->setRequest($request);

            //set the factory for easy use access throughout the controllers
            $controller[0]->setFactory($this->factory);

            //run any initialize functions
            $controller[0]->initialize($event);

            //update the user's activity marker
            if (!($controller[0] instanceof InstallController) && !defined('MAUTIC_ACTIVITY_CHECKED') && !defined('MAUTIC_INSTALLER')) {
                //prevent multiple updates
                $user = $this->factory->getUser();
                //slight delay to prevent too many updates
                //note that doctrine will return in current timezone so we do not have to worry about that
                $delay = new \DateTime();
                $delay->setTimestamp(strtotime('2 minutes ago'));

                /** @var \Mautic\UserBundle\Model\UserModel $userModel */
                $userModel = $this->factory->getModel('user');
                if ($user instanceof User && $user->getLastActive() < $delay && $user->getId()) {
                    $userModel->getRepository()->setLastActive($user);
                }

                $session = $this->factory->getSession();

                $delay = new \DateTime();
                $delay->setTimestamp(strtotime('15 minutes ago'));

                $lastOnlineStatusCleanup = $session->get('mautic.online.status.cleanup', $delay);

                if ($lastOnlineStatusCleanup <= $delay) {
                    $userModel->getRepository()->updateOnlineStatuses();
                    $session->set('mautic.online.status.cleanup', new \DateTime());
                }

                define('MAUTIC_ACTIVITY_CHECKED', 1);
            }
        }
    }

    /**
     * @param MenuEvent $event
     *
     * @return void
     */
    public function onBuildMenu(MenuEvent $event)
    {
        $this->buildMenu($event);
    }

    /**
     * @param RouteEvent $event
     *
     * @return void
     */
    public function onBuildRoute(RouteEvent $event)
    {
        $this->buildRoute($event);
    }

    /**
     * @param IconEvent $event
     *
     * @return void
     */
    public function onFetchIcons(IconEvent $event)
    {
        $this->buildIcons($event);
    }
}
