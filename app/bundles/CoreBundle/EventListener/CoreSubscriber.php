<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\EventListener;

use Mautic\CoreBundle\Controller\MauticController;
use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\IconEvent;
use Mautic\CoreBundle\Event\MenuEvent;
use Mautic\CoreBundle\Event\RouteEvent;
use Mautic\CoreBundle\Helper\BundleHelper;
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
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

/**
 * Class CoreSubscriber.
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
        AssetsHelper $assetsHelper,
        CoreParametersHelper $coreParametersHelper,
        SecurityContext $securityContext,
        UserModel $userModel
    ) {
        $this->bundleHelper         = $bundleHelper;
        $this->menuHelper           = $menuHelper;
        $this->userHelper           = $userHelper;
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
        return [
            KernelEvents::CONTROLLER => [
                ['onKernelController', 0],
                ['onKernelRequestAddGlobalJS', 0],
            ],
            CoreEvents::BUILD_MENU            => ['onBuildMenu', 9999],
            CoreEvents::BUILD_ROUTE           => ['onBuildRoute', 0],
            CoreEvents::FETCH_ICONS           => ['onFetchIcons', 9999],
            SecurityEvents::INTERACTIVE_LOGIN => ['onSecurityInteractiveLogin', 0],
        ];
    }

    /**
     * Add mauticForms in js script tag for Froala.
     *
     * @param FilterControllerEvent $event
     */
    public function onKernelRequestAddGlobalJS(FilterControllerEvent $event)
    {
        if (defined('MAUTIC_INSTALLER') || $this->userHelper->getUser()->isGuest() || !$event->isMasterRequest()) {
            return;
        }

        $list = $this->em->getRepository('MauticFormBundle:Form')->getSimpleList();

        $mauticForms = json_encode($list, JSON_FORCE_OBJECT | JSON_PRETTY_PRINT);

        $this->assetsHelper->addScriptDeclaration("var mauticForms = {$mauticForms};");
    }

    /**
     * Set vars on login.
     *
     * @param InteractiveLoginEvent $event
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
     * Populates namespace, bundle, controller, and action into request to be used throughout application.
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

            // set the factory for easy use access throughout the controllers
            // @deprecated To be removed in 3.0
            $controller[0]->setFactory($this->factory);

            // set the user as well
            $controller[0]->setUser($this->userHelper->getUser());

            // and the core parameters helper
            $controller[0]->setCoreParametersHelper($this->coreParametersHelper);

            // and the dispatcher
            $controller[0]->setDispatcher($this->dispatcher);

            // and the translator
            $controller[0]->setTranslator($this->translator);

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

                $session = $request->getSession();

                if ($session) {
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
    }

    /**
     * @param MenuEvent $event
     */
    public function onBuildMenu(MenuEvent $event)
    {
        $name    = $event->getType();
        $bundles = $this->bundleHelper->getMauticBundles(true);
        foreach ($bundles as $bundle) {
            if (!empty($bundle['config']['menu'][$name])) {
                $menu = $bundle['config']['menu'][$name];
                $event->addMenuItems(
                    [
                        'priority' => !isset($menu['priority']) ? 9999 : $menu['priority'],
                        'items'    => !isset($menu['items']) ? $menu : $menu['items'],
                    ]
                );
            }
        }
    }

    /**
     * @param RouteEvent $event
     */
    public function onBuildRoute(RouteEvent $event)
    {
        $type       = $event->getType();
        $bundles    = $this->bundleHelper->getMauticBundles(true);
        $collection = $event->getCollection();

        foreach ($bundles as $bundle) {
            if (!empty($bundle['config']['routes'][$type])) {
                foreach ($bundle['config']['routes'][$type] as $name => $details) {
                    if ('api' == $type && !empty($details['standard_entity'])) {
                        $standards = [
                            'getall' => [
                                'action' => 'getEntities',
                                'method' => 'GET',
                                'path'   => '',
                            ],
                            'getone' => [
                                'action' => 'getEntity',
                                'method' => 'GET',
                                'path'   => '/{id}',
                            ],
                            'new' => [
                                'action' => 'newEntity',
                                'method' => 'POST',
                                'path'   => '/new',
                            ],
                            'newbatch' => [
                                'action' => 'newEntities',
                                'method' => 'POST',
                                'path'   => '/batch/new',
                            ],
                            'editbatchput' => [
                                'action' => 'editEntities',
                                'method' => 'PUT',
                                'path'   => '/batch/edit',
                            ],
                            'editbatchpatch' => [
                                'action' => 'editEntities',
                                'method' => 'PATCH',
                                'path'   => '/batch/edit',
                            ],
                            'editput' => [
                                'action' => 'editEntity',
                                'method' => 'PUT',
                                'path'   => '/{id}/edit',
                            ],
                            'editpatch' => [
                                'action' => 'editEntity',
                                'method' => 'PATCH',
                                'path'   => '/{id}/edit',
                            ],
                            'deletebatch' => [
                                'action' => 'deleteEntities',
                                'method' => 'DELETE',
                                'path'   => '/batch/delete',
                            ],
                            'delete' => [
                                'action' => 'deleteEntity',
                                'method' => 'DELETE',
                                'path'   => '/{id}/delete',
                            ],
                        ];

                        foreach (['name', 'path', 'controller'] as $required) {
                            if (empty($details[$required])) {
                                throw new \InvalidArgumentException("$bundle.$name must have $required defined");
                            }
                        }

                        $routeName  = 'mautic_api_'.$details['name'].'_';
                        $pathBase   = $details['path'];
                        $controller = $details['controller'];
                        foreach ($standards as $standardName => $standardDetails) {
                            if (!empty($details['supported_endpoints']) && !in_array($standardName, $details['supported_endpoints'])) {
                                // Not supported so ignore
                                continue;
                            }

                            $routeDetails = array_merge(
                                $standardDetails,
                                [
                                    'path'       => $pathBase.$standardDetails['path'],
                                    'controller' => $controller.':'.$standardDetails['action'],
                                    'method'     => $standardDetails['method'],
                                ]
                            );
                            $this->addRouteToCollection($collection, $type, $routeName.$standardName, $routeDetails);
                        }
                    } else {
                        $this->addRouteToCollection($collection, $type, $name, $details);
                    }
                }
            }
        }
    }

    /**
     * @param IconEvent $event
     */
    public function onFetchIcons(IconEvent $event)
    {
        $session = $this->request->getSession();
        $icons   = $session->get('mautic.menu.icons', []);

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

    /**
     * @param RouteCollection $collection
     * @param                 $type
     * @param                 $name
     * @param                 $details
     */
    private function addRouteToCollection(RouteCollection $collection, $type, $name, $details)
    {
        // Set defaults and controller
        $defaults = (!empty($details['defaults'])) ? $details['defaults'] : [];
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
        $requirements = (!empty($details['requirements'])) ? $details['requirements'] : [];

        // Set some very commonly used defaults and requirements
        if (strpos($details['path'], '{page}') !== false) {
            if (!isset($defaults['page'])) {
                $defaults['page'] = 0;
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
                $requirements['objectId'] = '[a-zA-Z0-9_]+';
            }
        }
        if ($type == 'api') {
            if (strpos($details['path'], '{id}') !== false) {
                if (!isset($requirements['page'])) {
                    $requirements['id'] = '\d+';
                }
            }

            if (preg_match_all('/\{(.*?Id)\}/', $details['path'], $matches)) {
                // Force digits for IDs
                foreach ($matches[1] as $match) {
                    if (!isset($requirements[$match])) {
                        $requirements[$match] = '\d+';
                    }
                }
            }
        }

        // Add the route
        $collection->add($name, new Route($details['path'], $defaults, $requirements, [], '', [], $method));
    }
}
