<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\EventListener;

use Mautic\CoreBundle\Controller\MauticController;
use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\MenuEvent;
use Mautic\CoreBundle\Event\RouteEvent;
use Mautic\ApiBundle\Event as ApiEvents;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

/**
 * Class CoreSubscriber
 *
 * @package Mautic\CoreBundle\EventListener
 */
class CoreSubscriber extends CommonSubscriber
{

    /**
     * @return array
     */
    static public function getSubscribedEvents()
    {
        return array(
            KernelEvents::CONTROLLER          => array('onKernelController', 0),
            KernelEvents::REQUEST             => array('onKernelRequest', 0),
            CoreEvents::BUILD_MENU            => array('onBuildMenu', 9999),
            CoreEvents::BUILD_ADMIN_MENU      => array('onBuildAdminMenu', 9999),
            CoreEvents::BUILD_ROUTE           => array('onBuildRoute', 0),
            SecurityEvents::INTERACTIVE_LOGIN => array('onSecurityInteractiveLogin', 0)
        );
    }

    /**
     * Set default timezone/locale
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $currentUser = $this->factory->getUser();

        //set the user's timezone
        if (is_object($currentUser))
            $tz = $currentUser->getTimezone();

        if (empty($tz))
            $tz = $this->params['default_timezone'];

        date_default_timezone_set($tz);

        //set the user's default locale
        $request = $event->getRequest();
        if (!$request->hasPreviousSession()) {
            return;
        }

        // try to see if the locale has been set as a _locale routing parameter
        if ($locale = $request->attributes->get('_locale')) {
            $request->getSession()->set('_locale', $locale);
        } else {
            if (is_object($currentUser))
                $locale = $currentUser->getLocale();
            if (empty($locale))
                $locale = $this->params['locale'];

            // if no explicit locale has been set on this request, use one from the session
            $request->setLocale($request->getSession()->get('_locale', $locale));
        }
    }

    /**
     * Set vars on login
     *
     * @param InteractiveLoginEvent $event
     */
    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event)
    {
        $session = $event->getRequest()->getSession();
        if ($this->securityContext->isGranted('IS_AUTHENTICATED_FULLY') ||
            $this->securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            $user = $event->getAuthenticationToken()->getUser();

            //set a session var for filemanager to know someone is logged in
            $session->set('mautic.user', $user->getId());

            //mark the user as last logged in
            $user = $this->factory->getUser();
            if ($user instanceof User) {
                $this->factory->getModel('user.user')->getRepository()->setLastLogin($user);
            }
        } else {
            $session->remove('mautic.user');
        }

        $session->set('mautic.basepath', $event->getRequest()->getBasePath());
    }

    /**
     * Populates namespace, bundle, controller, and action into request to be used throughout application
     *
     * @param FilterControllerEvent $event
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
        }

        //update the user's activity marker
        $user = $this->factory->getUser();
        //slight delay to prevent too many updates
        //note that doctrine will return in current timezone so we do not have to worry about that
        $delay = new \DateTime();
        $delay->setTimestamp(strtotime('2 minutes ago'));
        if ($user instanceof User && $user->getLastActive() < $delay) {
            $this->factory->getModel('user.user')->getRepository()->setLastActive($user);
        }
    }

    /**
     * @param MenuEvent $event
     */
    public function onBuildMenu (MenuEvent $event)
    {
        $this->buildMenu($event, 'main');
    }

    /**
     * @param MenuEvent $event
     */
    public function onBuildAdminMenu (MenuEvent $event)
    {
        $this->buildMenu($event, 'admin');
    }

    /**
     * Find and add menu items
     *
     * @param MenuEvent $event
     * @param           $name
     */
    protected function buildMenu(MenuEvent $event, $name)
    {
        $security = $event->getSecurity();
        $request  = $this->factory->getRequest();

        $bundles = $this->factory->getParameter('bundles');
        $menuItems = array();
        foreach ($bundles as $bundle) {
            //check common place
            $path = $bundle['directory'] . "/Config/menu/$name.php";
            if (!file_exists($path)) {
                if ($name == 'main') {
                    //else check for just a menu.php file
                    $path = $bundle['directory'] . "/Config/menu.php";
                }
                $recheck = true;
            } else {
                $recheck = false;
            }

            if (!$recheck || file_exists($path)) {
                $config      = include $path;
                $menuItems[] = array(
                    'priority' => !isset($config['priority']) ? 9999 : $config['priority'],
                    'items'    => !isset($config['items'])    ? $config : $config['items']
                );
            }
        }

        usort($menuItems, function($a, $b) {
            $ap = $a['priority'];
            $bp = $b['priority'];

            if ($ap == $bp) {
                return 0;
            }

            return ($ap < $bp) ? -1 : 1;
        });

        foreach ($menuItems as $items) {
            $event->addMenuItems($items['items']);
        }
    }

    /**
     * @param RouteEvent $event
     */
    public function onBuildRoute (RouteEvent $event)
    {
        $this->buildRoute($event, 'routing');
    }

    /**
     * @param RouteEvent $event
     */
    public function onBuildApiRoute(RouteEvent $event)
    {
        $this->buildRoute($event, 'api');
    }

    /**
     * Get routing from bundles and add to Routing event
     *
     * @param $event
     * @param $name
     */
    protected function buildRoute(RouteEvent $event, $name)
    {
        $bundles = $this->factory->getParameter('bundles');

        $routes = array();
        foreach ($bundles as $bundle) {
            $routing = $bundle['directory'] . "/Config/$name.php";
            if (file_exists($routing)) {
                $event->addRoutes($routing);
            } else {
                $routing = $bundle['directory'] . "/Config/routing/$name.php";
                if (file_exists($routing)) {
                    $event->addRoutes($routing);
                }
            }
        }
    }
}