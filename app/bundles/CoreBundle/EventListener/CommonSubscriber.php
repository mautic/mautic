<?php
/**
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
namespace Mautic\CoreBundle\EventListener;

use Mautic\CoreBundle\Event as MauticEvents;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Menu\MenuHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class CoreSubscriber.
 */
class CommonSubscriber implements EventSubscriberInterface
{
    /**
     * @var \Symfony\Component\HttpFoundation\Request
     */
    protected $request;

    /**
     * @var \Symfony\Bundle\FrameworkBundle\Templating\DelegatingEngine
     */
    protected $templating;

    /**
     * @var \JMS\Serializer\Serializer
     */
    protected $serializer;

    /**
     * @var \Mautic\CoreBundle\Security\Permissions\CorePermissions
     */
    protected $security;

    /**
     * @var \Symfony\Component\EventDispatcher\ContainerAwareEventDispatcher
     */
    protected $dispatcher;

    /**
     * @var MauticFactory
     */
    protected $factory;

    /**
     * @var array
     */
    protected $params;

    /**
     * @var \Symfony\Bundle\FrameworkBundle\Translation\Translator
     */
    protected $translator;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * @var \Symfony\Bundle\FrameworkBundle\Routing\Router
     */
    protected $router;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $this->factory    = $factory;
        $this->templating = $factory->getTemplating();
        $this->request    = $factory->getRequest();
        $this->security   = $factory->getSecurity();
        $this->serializer = $factory->getSerializer();
        $this->params     = $factory->getSystemParameters();
        $this->dispatcher = $factory->getDispatcher();
        $this->translator = $factory->getTranslator();
        $this->em         = $factory->getEntityManager();
        $this->router     = $factory->getRouter();

        $this->init();
    }

    /**
     * Post __construct setup so that inheriting classes don't have to pass all the arguments.
     */
    protected function init()
    {
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [];
    }

    /**
     * Find and add menu items.
     *
     * @param MauticEvents\MenuEvent $event
     */
    protected function buildMenu(MauticEvents\MenuEvent $event)
    {
        $name    = $event->getType();
        $bundles = $this->factory->getMauticBundles(true);
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
     * Find and add menu items.
     *
     * @param MauticEvents\IconEvent $event
     */
    protected function buildIcons(MauticEvents\IconEvent $event)
    {
        $session = $this->factory->getSession();
        $icons   = $session->get('mautic.menu.icons', []);

        if (empty($icons)) {
            $bundles = $this->factory->getMauticBundles(true);
            /** @var MenuHelper $menuHelper */
            $menuHelper = $this->factory->getHelper('menu');
            foreach ($bundles as $bundle) {
                if (!empty($bundle['config']['menu']['main'])) {
                    $items = (!isset($bundle['config']['menu']['main']['items']) ? $bundle['config']['menu']['main'] : $bundle['config']['menu']['main']['items']);
                }

                if (!empty($items)) {
                    $menuHelper->createMenuStructure($items);
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
     * Get routing from bundles and add to Routing event.
     *
     * @param MauticEvents\RouteEvent $event
     */
    protected function buildRoute(MauticEvents\RouteEvent $event)
    {
        $type       = $event->getType();
        $bundles    = $this->factory->getMauticBundles(true);
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
                            $details = array_merge(
                                $standardDetails,
                                [
                                    'path'       => $pathBase.$standardDetails['path'],
                                    'controller' => $controller.':'.$standardDetails['action'],
                                    'method'     => $standardDetails['method'],
                                ]
                            );
                            $this->addRouteToCollection($collection, $type, $routeName.$standardName, $details);
                        }
                    } else {
                        $this->addRouteToCollection($collection, $type, $name, $details);
                    }
                }
            }
        }
    }

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
                $requirements['objectId'] = '[a-zA-Z0-9_]+';
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
