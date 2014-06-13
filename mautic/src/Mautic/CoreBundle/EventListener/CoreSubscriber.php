<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\EventListener;

use Mautic\CoreBundle\Controller\EventsController;
use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\MenuEvent;
use Mautic\CoreBundle\Event\RouteEvent;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

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
            KernelEvents::CONTROLLER        => array('onKernelController', 0),
            KernelEvents::REQUEST           => array('onKernelRequest', 9999),
            CoreEvents::BUILD_MENU          => array('onBuildMenu', 9999),
            CoreEvents::BUILD_ADMIN_MENU    => array('onBuildAdminMenu', 9999),
            CoreEvents::BUILD_ROUTE         => array('onBuildRoute', 0)
        );
    }

    /**
     * Set default timezone/locale
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $currentUser = $this->security->getCurrentUser();

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
        if ($controller[0] instanceof EventsController) {

            //populate request attributes with  namespace, bundle, controller, and action names for use in bundle controllers and templates
            $request        = $event->getRequest();
            $matches        = array();
            $controllerName = $request->attributes->get('_controller');
            preg_match('/(.*)\\\(.*)Bundle\\\Controller\\\(.*)Controller::(.*)Action/', $controllerName, $matches);

            if (!empty($matches)) {
                $request->attributes->set('namespace', $matches[1]);
                $request->attributes->set('bundle', $matches[2]);
                $request->attributes->set('controller', $matches[3]);
                $request->attributes->set('action', $matches[4]);
            } else {
                preg_match('/Mautic(.*)Bundle:(.*):(.*)/', $controllerName, $matches);
                if (!empty($matches)) {
                    $request->attributes->set('namespace', 'Mautic');
                    $request->attributes->set('bundle', $matches[1]);
                    $request->attributes->set('controller', $matches[2]);
                    $request->attributes->set('action', $matches[3]);
                }
            }

            //also set the request for easy access throughout controllers
            $controller[0]->setRequest($request);

            //run any initialize functions
            $controller[0]->initialize($event);
        }
    }

    /**
     * @param MenuEvent $event
     */
    public function onBuildMenu(MenuEvent $event)
    {
        $path = __DIR__ . "/../Resources/config/menu/main.php";
        $items = include $path;
        $event->addMenuItems($items);
    }

    /**
     * @param MenuEvent $event
     */
    public function onBuildAdminMenu(MenuEvent $event)
    {
        $path = __DIR__ . "/../Resources/config/menu/admin.php";
        $items = include $path;
        $event->addMenuItems($items);
    }

    /**
     * @param RouteEvent $event
     */
    public function onBuildRoute(RouteEvent $event)
    {
        $path = __DIR__ . "/../Resources/config/routing.php";
        $event->addRoutes($path);
    }
}