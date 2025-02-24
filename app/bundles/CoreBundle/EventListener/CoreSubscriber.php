<?php

namespace Mautic\CoreBundle\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\IconEvent;
use Mautic\CoreBundle\Event\MenuEvent;
use Mautic\CoreBundle\Event\RouteEvent;
use Mautic\CoreBundle\Helper\BundleHelper;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Menu\MenuHelper;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Event\LoginEvent;
use Mautic\UserBundle\Model\UserModel;
use Mautic\UserBundle\UserEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

class CoreSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private BundleHelper $bundleHelper,
        private MenuHelper $menuHelper,
        private UserHelper $userHelper,
        private CoreParametersHelper $coreParametersHelper,
        private AuthorizationCheckerInterface $securityContext,
        private UserModel $userModel,
        private EventDispatcherInterface $dispatcher,
        private RequestStack $requestStack,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CoreEvents::BUILD_MENU            => ['onBuildMenu', 9999],
            CoreEvents::BUILD_ROUTE           => ['onBuildRoute', 0],
            CoreEvents::FETCH_ICONS           => ['onFetchIcons', 9999],
            SecurityEvents::INTERACTIVE_LOGIN => ['onSecurityInteractiveLogin', 0],
        ];
    }

    /**
     * Set vars on login.
     */
    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event): void
    {
        if (defined('MAUTIC_INSTALLER')) {
            return;
        }

        $session = $event->getRequest()->getSession();
        if ($this->securityContext->isGranted('IS_AUTHENTICATED_FULLY') || $this->securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            /** @var User $user */
            $user = $event->getAuthenticationToken()->getUser();

            // set a session var for filemanager to know someone is logged in
            $session->set('mautic.user', $user->getId());

            // mark the user as last logged in
            $user = $this->userHelper->getUser();
            if ($user instanceof User) {
                $this->userModel->getRepository()->setLastLogin($user);

                // Set the timezone and locale in session while we have it since Symfony dispatches the onKernelRequest prior to the
                // firewall setting the known user
                $tz = $user->getTimezone();
                if (empty($tz)) {
                    $tz = $this->coreParametersHelper->get('default_timezone');
                }
                $session->set('_timezone', $tz);

                $locale = $user->getLocale();
                if (empty($locale)) {
                    $locale = $this->coreParametersHelper->get('locale');
                }
                $session->set('_locale', $locale);
            }

            // dispatch on login events
            if ($this->dispatcher->hasListeners(UserEvents::USER_LOGIN)) {
                $loginEvent = new LoginEvent($this->userHelper->getUser());
                $this->dispatcher->dispatch($loginEvent, UserEvents::USER_LOGIN);
            }
        } else {
            $session->remove('mautic.user');
        }
    }

    public function onBuildMenu(MenuEvent $event): void
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

    public function onBuildRoute(RouteEvent $event): void
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
                                    'controller' => $controller.'::'.$standardDetails['action'].'Action',
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

    public function onFetchIcons(IconEvent $event): void
    {
        $session = $this->requestStack->getCurrentRequest()->getSession();
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
                                if (str_ends_with($id[1], 's')) {
                                    $event->addIcon(rtrim($id[1], 's'), $item['iconClass']);
                                }
                                $event->addIcon($id[1], $item['iconClass']);
                            }
                        }
                    }
                }
            }
            unset($bundles);

            $icons = $event->getIcons();
            $session->set('mautic.menu.icons', $icons);
        } else {
            $event->setIcons($icons);
        }
    }

    private function addRouteToCollection(RouteCollection $collection, $type, $name, $details): void
    {
        // Set defaults and controller
        $defaults = (!empty($details['defaults'])) ? $details['defaults'] : [];
        if (isset($details['controller'])) {
            $defaults['_controller'] = $details['controller'];
        }
        if (isset($details['format'])) {
            $defaults['_format'] = $details['format'];
        } elseif ('api' == $type) {
            $defaults['_format'] = 'json';
        }
        $method = [];
        if (isset($details['method'])) {
            $method = (array) $details['method'];
        } elseif ('api' === $type) {
            $method = ['GET'];
        }
        // Set requirements
        $requirements = (!empty($details['requirements'])) ? $details['requirements'] : [];

        // Set some very commonly used defaults and requirements
        if (str_contains($details['path'], '{page}')) {
            if (!isset($defaults['page'])) {
                $defaults['page'] = 0;
            }
            if (!isset($requirements['page'])) {
                $requirements['page'] = '\d+';
            }
        }
        if (str_contains($details['path'], '{objectId}')) {
            if (!isset($defaults['objectId'])) {
                // Set default to 0 for the "new" actions
                $defaults['objectId'] = 0;
            }
            if (!isset($requirements['objectId'])) {
                // Only allow alphanumeric and _- for objectId
                $requirements['objectId'] = '[a-zA-Z0-9_-]+';
            }
        }
        if ('api' == $type) {
            if (str_contains($details['path'], '{id}')) {
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
