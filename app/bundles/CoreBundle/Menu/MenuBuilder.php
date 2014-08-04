<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
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
 *
 * @package Mautic\CoreBundle\Menu
 */
class MenuBuilder
{
    private $factory;
    private $matcher;
    private $security;
    private $dispatcher;
    private $request;

    /**
     * @param FactoryInterface $knpFactory
     * @param MatcherInterface $matcher
     * @param MauticFactory    $factory
     */
    public function __construct(FactoryInterface $knpFactory, MatcherInterface $matcher, MauticFactory $factory)
    {
        $this->factory    = $knpFactory;
        $this->matcher    = $matcher;
        $this->security   = $factory->getSecurity();
        $this->dispatcher = $factory->getDispatcher();
        $this->request    = $factory->getRequest();
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
            $event      = new MenuEvent($this->security);
            $this->dispatcher->dispatch(CoreEvents::BUILD_MENU, $event);
            $menuItems  = $event->getMenuItems();
            $menu       = $loader->load($menuItems);
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
            $event      = new MenuEvent($this->security);
            $this->dispatcher->dispatch(CoreEvents::BUILD_ADMIN_MENU, $event);
            $menuItems  = $event->getMenuItems();
            $adminMenu  = $loader->load($menuItems);
        }
        return $adminMenu;
    }

    /**
     * Converts navigation object into breadcrumbs
     *
     */
    public function breadcrumbsMenu() {
        $menu  = $this->mainMenu($this->request);

        //check for overrideRoute in request from an ajax content request
        $forRouteUri  = $this->request->get("overrideRouteUri", "current");
        $forRouteName = $this->request->get("overrideRouteName", '');
        $current      = $this->getCurrentMenuItem($menu, $forRouteUri, $forRouteName);

        //if empty, check the admin menu
        if (empty($current)) {
            $admin   = $this->adminMenu($this->request);
            $current = $this->getCurrentMenuItem($admin, $forRouteUri, $forRouteName);
        }

        //if still empty, default to root
        if (empty($current)) {
            $current = $menu->getRoot();
        }

        return $current;
    }

    /**
     * Used by breadcrumbs to determine active link
     *
     * @param $menu
     * @return null
     */
    public function getCurrentMenuItem($menu, $forRouteUri, $forRouteName)
    {
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

            if ($item->getChildren()
                && $current_child = $this->getCurrentMenuItem($item, $forRouteUri, $forRouteName)) {
                return $current_child;
            }
        }

        return null;
    }
}