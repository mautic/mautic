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
use Knp\Menu\Matcher\MatcherInterface;
use Knp\Menu\Loader\ArrayLoader;
use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\MenuEvent;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Security\Core\SecurityContext;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;

/**
 * Class MenuBuilder
 *
 * @package Mautic\CoreBundle\Menu
 */
class MenuBuilder extends ContainerAware
{
    private   $factory;
    private   $matcher;
    private   $securityContext;
    private   $mauticSecurity;
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }


    /**
     * @param FactoryInterface $factory
     * @param MatcherInterface $matcher
     * @param SecurityContext  $securityContext
     * @param CorePermissions  $permissions
     */
    public function __construct(FactoryInterface $factory,
                                MatcherInterface $matcher,
                                SecurityContext $securityContext,
                                CorePermissions $permissions
    )
    {
        $this->factory         = $factory;
        $this->matcher         = $matcher;
        $this->securityContext = $securityContext;
        $this->mauticSecurity  = $permissions;
    }

    /**
     * Generate menu navigation object
     *
     * @param Request $request
     * @return \Knp\Menu\ItemInterface
     */
    public function mainMenu(Request $request)
    {
        static $menu;

        if (empty($menu)) {
            $loader = new ArrayLoader($this->factory);

            //dispatch the MENU_BUILD event to retrieve bundle menu items
            $event      = new MenuEvent($this->securityContext, $this->mauticSecurity);
            $this->container->get('event_dispatcher')->dispatch(CoreEvents::BUILD_MENU, $event);
            $menuItems  = $event->getMenuItems();
            $menu       = $loader->load($menuItems);
        }
        return $menu;
    }

    /**
     * Generate admin menu navigation object
     *
     * @param Request $request
     * @return \Knp\Menu\ItemInterface
     */
    public function adminMenu(Request $request)
    {
        static $adminMenu;

        if (empty($adminMenu)) {
            $loader = new ArrayLoader($this->factory);

            //dispatch the MENU_BUILD event to retrieve bundle menu items
            $event      = new MenuEvent($this->securityContext, $this->mauticSecurity);
            $this->container->get('event_dispatcher')->dispatch(CoreEvents::BUILD_ADMIN_MENU, $event);
            $menuItems  = $event->getMenuItems();
            $adminMenu  = $loader->load($menuItems);
        }
        return $adminMenu;
    }

    /**
     * Converts navigation object into breadcrumbs
     *
     * @param Request $request
     */
    public function breadcrumbsMenu(Request $request) {
        $menu  = $this->mainMenu($request);

        //check for overrideRoute in request from an ajax content request
        $forRouteUri  = $request->get("overrideRouteUri", "current");
        $forRouteName = $request->get("overrideRouteName", '');
        $current      = $this->getCurrentMenuItem($menu, $forRouteUri, $forRouteName);

        //if empty, check the admin menu
        if (empty($current)) {
            $admin   = $this->adminMenu($request);
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