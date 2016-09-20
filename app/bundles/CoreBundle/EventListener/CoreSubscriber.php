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
use Mautic\CoreBundle\Helper\BundleHelper;
use Mautic\CoreBundle\Helper\CookieHelper;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Menu\MenuHelper;
use Mautic\CoreBundle\Templating\Helper\AssetsHelper;
use Mautic\InstallBundle\Controller\InstallController;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Event\LoginEvent;
use Mautic\UserBundle\Model\UserModel;
use Mautic\UserBundle\UserEvents;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Route;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

/**
 * Class CoreSubscriber
 */
class CoreSubscriber extends CommonSubscriber
{
    /**
     * @var BundleHelper
     */
    protected $bundleHelper;

    /**
     * @var MenuHelper
     */
    protected $menuHelper;

    /**
     * @var UserHelper
     */
    protected $userHelper;

    /**
     * @var CookieHelper
     */
    protected $cookieHelper;

    /**
     * @var AssetsHelper
     */
    protected $assetsHelper;

    /**
     * @var SecurityContext
     */
    protected $securityContext;

    /**
     * @var UserModel
     */
    protected $userModel;

    /**
     * @var CoreParametersHelper
     */
    protected $coreParametersHelper;

    public function __construct(
        BundleHelper $bundleHelper,
        MenuHelper $menuHelper,
        UserHelper $userHelper,
        CookieHelper $cookieHelper,
        AssetsHelper $assetsHelper,
        CoreParametersHelper $coreParametersHelper,
        SecurityContext $securityContext,
        UserModel $userModel
    )
    {
        $this->bundleHelper         = $bundleHelper;
        $this->menuHelper           = $menuHelper;
        $this->userHelper           = $userHelper;
        $this->cookieHelper         = $cookieHelper;
        $this->assetsHelper         = $assetsHelper;
        $this->securityContext      = $securityContext;
        $this->userModel            = $userModel;
        $this->coreParametersHelper = $coreParametersHelper;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::CONTROLLER          => array('onKernelController', 0),
            KernelEvents::REQUEST             => [
                ['onKernelRequestSetTimezone', 9999],
                ['onKernelRequestSetLocale', 15], // Must be 15 to load after Symfony's default Locale listener
                ['onKernelRequestAddGlobalJS', 0]
            ],
            CoreEvents::BUILD_MENU            => array('onBuildMenu', 9999),
            CoreEvents::BUILD_ROUTE           => array('onBuildRoute', 0),
            CoreEvents::FETCH_ICONS           => array('onFetchIcons', 9999),
            SecurityEvents::INTERACTIVE_LOGIN => array('onSecurityInteractiveLogin', 0)
        );
    }

    /**
     * Set timezone
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequestSetTimezone(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        if (!$request->hasPreviousSession()) {
            return;
        }

        // Set date/time
        date_default_timezone_set($request->getSession()->get('_timezone', $this->params['default_timezone']));

        // Set a cookie with session name for filemanager
        $sessionName = $request->cookies->get('mautic_session_name');
        if ($sessionName != session_name()) {
            $this->cookieHelper->setCookie('mautic_session_name', session_name(), null);
        }
    }

    /**
     * Set default locale
     *
     * @param GetResponseEvent $event
     *
     * @return void
     */
    public function onKernelRequestSetLocale(GetResponseEvent $event)
    {
        // Set the user's default locale
        $request = $event->getRequest();
        if (!$request->hasPreviousSession()) {
            return;
        }

        // Set locale
        if ($locale = $request->attributes->get('_locale')) {
            $request->getSession()->set('_locale', $locale);
        } else {
            $request->setLocale($request->getSession()->get('_locale', $this->params['locale']));
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

        $list = $this->em->getRepository('MauticFormBundle:Form')->getSimpleList();

        $mauticForms = json_encode($list, JSON_FORCE_OBJECT | JSON_PRETTY_PRINT);

        $this->assetsHelper->addScriptDeclaration("var mauticForms = {$mauticForms};");
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

        if ($this->securityContext->isGranted('IS_AUTHENTICATED_FULLY') || $this->securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {

            $user = $event->getAuthenticationToken()->getUser();

            //set a session var for filemanager to know someone is logged in
            $session->set('mautic.user', $user->getId());

            //mark the user as last logged in
            $user = $this->userHelper->getUser();
            if ($user instanceof User) {
                $this->userModel->setOnlineStatus('online');

                $this->userModel->getRepository()->setLastLogin($user);

                // Set the timezone and locale in session while we have it since Symfony dispatches the onKernelRequest prior to the
                // firewall setting the known user
                $tz = $user->getTimezone();
                if (empty($tz)) {
                    $tz = $this->params['default_timezone'];
                }
                $session->set('_timezone', $tz);

                $locale = $user->getLocale();
                if (empty($locale)) {
                    $locale = $this->params['locale'];
                }
                $session->set('_locale', $locale);
            }

            //dispatch on login events
            if ($this->dispatcher->hasListeners(UserEvents::USER_LOGIN)) {
                $event = new LoginEvent($this->userHelper->getUser());
                $this->dispatcher->dispatch(UserEvents::USER_LOGIN, $event);
            }
        } else {
            $session->remove('mautic.user');
        }

        //set a couple variables used by filemanager
        $session->set('mautic.docroot', $event->getRequest()->server->get('DOCUMENT_ROOT'));
        $session->set('mautic.basepath', $event->getRequest()->getBasePath());
        $session->set('mautic.imagepath', $this->coreParametersHelper->getParameter('image_path'));
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
                $user = $this->userHelper->getUser();
                //slight delay to prevent too many updates
                //note that doctrine will return in current timezone so we do not have to worry about that
                $delay = new \DateTime();
                $delay->setTimestamp(strtotime('2 minutes ago'));

                if ($user instanceof User && $user->getLastActive() < $delay && $user->getId()) {
                    $this->userModel->getRepository()->setLastActive($user);
                }

                $session = $this->request->getSession();

                $delay = new \DateTime();
                $delay->setTimestamp(strtotime('15 minutes ago'));

                $lastOnlineStatusCleanup = $session->get('mautic.online.status.cleanup', $delay);

                if ($lastOnlineStatusCleanup <= $delay) {
                    $this->userModel->getRepository()->updateOnlineStatuses();
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
        $name = $event->getType();
        $bundles = $this->bundleHelper->getMauticBundles(true);
        foreach ($bundles as $bundle) {
            if (!empty($bundle['config']['menu'][$name])) {
                $menu = $bundle['config']['menu'][$name];
                $event->addMenuItems(
                    array(
                        'priority' => !isset($menu['priority']) ? 9999 : $menu['priority'],
                        'items'    => !isset($menu['items']) ? $menu : $menu['items']
                    )
                );
            }
        }
    }

    /**
     * @param RouteEvent $event
     *
     * @return void
     */
    public function onBuildRoute(RouteEvent $event)
    {
        $type       = $event->getType();
        $bundles    = $this->bundleHelper->getMauticBundles(true);
        $collection = $event->getCollection();

        foreach ($bundles as $bundle) {
            if (!empty($bundle['config']['routes'][$type])) {
                foreach ($bundle['config']['routes'][$type] as $name => $details) {
                    // Set defaults and controller
                    $defaults = (!empty($details['defaults'])) ? $details['defaults'] : array();
                    if (isset($details['controller'])) {
                        $defaults['_controller'] = $details['controller'];
                    }

                    if (isset($details['format'])) {
                        $defaults['_format'] = $details['format'];
                    } elseif ($type == 'api') {
                        $defaults['_format'] = 'json';
                    }

                    $method = '';

                    if (isset($details['method'])) {
                        $method = $details['method'];
                    } elseif ($type === 'api') {
                        $method = 'GET';
                    }

                    // Set requirements
                    $requirements = (!empty($details['requirements'])) ? $details['requirements'] : array();

                    // Set some very commonly used defaults and requirements
                    if (strpos($details['path'], '{page}') !== false) {
                        if (!isset($defaults['page'])) {
                            $defaults['page'] = 1;
                        }
                        if (!isset($requirements['page'])) {
                            $requirements['page'] = '\d+';
                        }
                    }
                    if (strpos($details['path'], '{objectId}') !== false) {
                        if (!isset($defaults['objectId'])) {
                            // Set default to 0 for the "new" actions
                            $defaults['objectId'] = 0;
                        }
                        if (!isset($requirements['objectId'])) {
                            // Only allow alphanumeric for objectId
                            $requirements['objectId'] = "[a-zA-Z0-9_]+";
                        }
                    }
                    if ($type == 'api' && strpos($details['path'], '{id}') !== false) {
                        if (!isset($requirements['page'])) {
                            $requirements['id'] = '\d+';
                        }
                    }

                    // Add the route
                    $collection->add($name, new Route($details['path'], $defaults, $requirements, [], '', [], $method));
                }
            }
        }
    }

    /**
     * @param IconEvent $event
     *
     * @return void
     */
    public function onFetchIcons(IconEvent $event)
    {
        $session = $this->request->getSession();
        $icons   = $session->get('mautic.menu.icons', array());

        if (empty($icons)) {
            $bundles = $this->bundleHelper->getMauticBundles(true);

            foreach ($bundles as $bundle) {
                if (!empty($bundle['config']['menu']['main'])) {
                    $items = (!isset($bundle['config']['menu']['main']['items']) ? $bundle['config']['menu']['main'] : $bundle['config']['menu']['main']['items']);
                }

                if (!empty($items)) {
                    $this->menuHelper->createMenuStructure($items);
                    foreach ($items as $item) {
                        if (isset($item['iconClass']) && isset($item['id'])) {
                            $id = explode('_', $item['id']);
                            if (isset($id[1])) {
                                // some bundle names are in plural, create also singular item
                                if (substr($id[1], -1) == 's') {
                                    $event->addIcon(rtrim($id[1], 's'), $item['iconClass']);
                                }
                                $event->addIcon($id[1], $item['iconClass']);
                            }
                        }
                    }
                }
            }
            unset($bundles, $menuHelper);

            $icons = $event->getIcons();
            $session->set('mautic.menu.icons', $icons);
        } else {
            $event->setIcons($icons);
        }
    }
}
