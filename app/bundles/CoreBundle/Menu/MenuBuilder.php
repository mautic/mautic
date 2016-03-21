<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Menu;

use Knp\Menu\FactoryInterface;
use Knp\Menu\Loader\ArrayLoader;
use Knp\Menu\Matcher\MatcherInterface;
use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\MenuEvent;
use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Routing\Router;

/**
 * Class MenuBuilder
 */
class MenuBuilder
{

    /**
     * @var FactoryInterface
     */
    private $factory;

    /**
     * @var MatcherInterface
     */
    private $matcher;

    /**
     * @var \Symfony\Component\EventDispatcher\ContainerAwareEventDispatcher
     */
    private $dispatcher;

    /**
     * @var \Mautic\CoreBundle\Menu\MenuHelper
     */
    private $menuHelper;

    /**
     * @param FactoryInterface $knpFactory
     * @param MatcherInterface $matcher
     * @param MauticFactory    $factory
     */
    public function __construct(FactoryInterface $knpFactory, MatcherInterface $matcher, MauticFactory $factory)
    {
        $this->factory    = $knpFactory;
        $this->matcher    = $matcher;
        $this->dispatcher = $factory->getDispatcher();
        $this->menuHelper = $factory->getHelper('menu');
    }

    /**
     * Generate menu navigation object
     *
     * @return \Knp\Menu\ItemInterface
     */
    public function mainMenu()
    {
        static $menu;

        if (empty($menu)) {
            $loader = new ArrayLoader($this->factory);

            //dispatch the MENU_BUILD event to retrieve bundle menu items
            $event = new MenuEvent($this->menuHelper, 'main');
            $this->dispatcher->dispatch(CoreEvents::BUILD_MENU, $event);
            $menuItems = $event->getMenuItems();
            $menu      = $loader->load($menuItems);
        }

        return $menu;
    }

    /**
     * Generate admin menu navigation object
     *
     * @return \Knp\Menu\ItemInterface
     */
    public function adminMenu()
    {
        static $adminMenu;

        if (empty($adminMenu)) {
            $loader = new ArrayLoader($this->factory);

            //dispatch the MENU_BUILD event to retrieve bundle menu items
            $event = new MenuEvent($this->menuHelper, 'admin');
            $this->dispatcher->dispatch(CoreEvents::BUILD_MENU, $event);
            $menuItems = $event->getMenuItems();
            $adminMenu = $loader->load($menuItems);
        }

        return $adminMenu;
    }

    /**
     * Used by breadcrumbs to determine active link
     *
     * @param \Knp\Menu\ItemInterface $menu
     * @param string                  $forRouteUri
     * @param string                  $forRouteName
     *
     * @return \Knp\Menu\ItemInterface|null
     */
    public function getCurrentMenuItem($menu, $forRouteUri, $forRouteName)
    {
        try {
            /** @var \Knp\Menu\ItemInterface $item */
            foreach ($menu as $item) {
                if ($forRouteUri == "current" && $this->matcher->isCurrent($item)) {
                    //current match
                    return $item;
                } else if ($forRouteUri != "current" && $item->getUri() == $forRouteUri) {
                    //route uri match
                    return $item;
                } else if (!empty($forRouteName) && $forRouteName == $item->getExtra("routeName")) {
                    //route name match
                    return $item;
                }

                if ($item->getChildren() && $current_child = $this->getCurrentMenuItem($item, $forRouteUri, $forRouteName)) {
                    return $current_child;
                }
            }
        } catch (\Exception $e) {
            //do nothing
        }

        return null;
    }
}
