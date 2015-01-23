<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\EventListener;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Menu\MenuHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Mautic\CoreBundle\Event as MauticEvents;
use Symfony\Component\Routing\Route;

/**
 * Class CoreSubscriber
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
     * @var \Mautic\AddonBundle\Helper\AddonHelper
     */
    protected $addonHelper;

    /**
     * @param MauticFactory $factory
     */
    public function __construct (MauticFactory $factory)
    {
        $this->factory         = $factory;
        $this->templating      = $factory->getTemplating();
        $this->request         = $factory->getRequest();
        $this->security        = $factory->getSecurity();
        $this->serializer      = $factory->getSerializer();
        $this->params          = $factory->getSystemParameters();
        $this->dispatcher      = $factory->getDispatcher();
        $this->translator      = $factory->getTranslator();
        $this->addonHelper     = $factory->getHelper('addon');
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array();
    }

    /**
     * Find and add menu items
     *
     * @param MauticEvents\MenuEvent $event
     * @param string                 $name
     *
     * @return void
     */
    protected function buildMenu(MauticEvents\MenuEvent $event, $name)
    {
        //for easy access in menu files
        $security = $event->getSecurity();
        $request  = $event->getRequest();
        $user     = $event->getUser();

        $bundles   = $this->factory->getParameter('bundles');
        $menuItems = array();
        foreach ($bundles as $bundle) {
            if (!empty($bundle['config']['menu'][$name])) {
                $this->loadMenuFromConfig($bundle['config']['menu'][$name]);
            } else {
                //check common place
                $path = $bundle['directory'] . "/Config/menu/$name.php";

                if (file_exists($path)) {
                    $config      = include $path;
                    $menuItems[] = array(
                        'priority' => !isset($config['priority']) ? 9999 : $config['priority'],
                        'items'    => !isset($config['items']) ? $config : $config['items']
                    );
                }
            }
        }

        // Cannot use the list from kernel here as enabled addons may have changed since
        $addons = $this->factory->getParameter('addon.bundles');
        foreach ($addons as $bundle) {
            if (!$this->addonHelper->isEnabled($bundle['bundle'])) {
                continue;
            }

            if (!empty($bundle['config']['menu'][$name])) {
                $this->loadMenuFromConfig($bundle['config']['menu'][$name]);
            } else {
                //check common place
                $path = $bundle['directory'] . "/Config/menu/$name.php";

                if (file_exists($path)) {
                    $config      = include $path;
                    $menuItems[] = array(
                        'priority' => !isset($config['priority']) ? 9999 : $config['priority'],
                        'items'    => !isset($config['items']) ? $config : $config['items']
                    );
                }
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
     * Add menu items from Mautic config file
     *
     * @param $menu
     */
    private function loadMenuFromConfig($menu)
    {
        return array(
            'priority' => !isset($menu['priority']) ? 9999 : $menu['priority'],
            'items'    => !isset($menu['items']) ? $menu : $menu['items']
        );
    }

    /**
     * Find and add menu items
     *
     * @param MauticEvents\IconEvent $event
     *
     * @return void
     */
    protected function buildIcons(MauticEvents\IconEvent $event)
    {
        $security = $event->getSecurity();
        $request  = $this->factory->getRequest();
        $user     = $this->factory->getUser();
        $bundles  = $this->factory->getParameter('bundles');

        $fetchIcons = function($bundle) use (&$event, $security, $request, $user) {
            if (!empty($bundle['config']['menu']['main'])) {
                $items = (!isset($bundle['config']['menu']['items']['main']) ? $bundle['config']['menu']['main'] : $bundle['config']['menu']['items']['main']);
            } else {
                //check common place
                $path = $bundle['directory'] . "/Config/menu/main.php";

                if (file_exists($path)) {
                    $config = include $path;
                    $items  = (!isset($config['items']) ? $config : $config['items']);
                }
            }

            if (!empty($items)) {
                MenuHelper::createMenuStructure($items, $security, $request, $user);
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
        };

        foreach ($bundles as $bundle) {
            $fetchIcons($bundle);
        }

        // Cannot use the list from kernel here as enabled addons may have changed since
        $addons = $this->factory->getParameter('addon.bundles');
        foreach ($addons as $bundle) {
            if (!$this->addonHelper->isEnabled($bundle['bundle'])) {
                continue;
            }
            $fetchIcons($bundle);
        }
    }


    /**
     * Get routing from bundles and add to Routing event
     *
     * @param MauticEvents\RouteEvent $event
     * @param string                  $name
     *
     * @return void
     */
    protected function buildRoute(MauticEvents\RouteEvent $event, $name)
    {
        $bundles = $this->factory->getParameter('bundles');
        foreach ($bundles as $bundle) {
            if (!empty($bundle['config']['routes'])) {
                $collection = $event->getCollection();
                $this->loadRoutesFromConfig($collection, $bundle['config']['routes']);
            } else {
                $routing = $bundle['directory'] . "/Config/routing/$name.php";
                if (file_exists($routing)) {
                    $event->addRoutes($routing);
                }
            }
        }

        // Cannot use the list from kernel here as enabled addons may have changed since
        $addons = $this->factory->getParameter('addon.bundles');
        foreach ($addons as $bundle) {
            if ($this->addonHelper->isEnabled($bundle['bundle'])) {
                if (!empty($bundle['config']['routes'])) {
                    $collection = $event->getCollection();
                    $this->loadRoutesFromConfig($collection, $bundle['config']['routes']);
                } else {
                    $routing = $bundle['directory'] . "/Config/routing/$name.php";
                    if (file_exists($routing)) {
                        $event->addRoutes($routing);
                    }
                }
            }
        }
    }

    /**
     * Adds routes from a Mautic config file
     *
     * @param $collection
     * @param $routes
     */
    private function loadRoutesFromConfig($collection, $routes)
    {
        foreach ($routes as $name => $details) {
            // Set defaults and controller
            $defaults = (!empty($details['defaults'])) ? $details['defaults'] : array();
            $defaults['_controller'] = $details['controller'];

            // Set requirements
            $requirements = (!empty($details['requirements'])) ? $details['requirements'] : array();

            // Add the route
            $collection->add($name, new Route($details['path'], $defaults, $requirements));
        }
    }
}
