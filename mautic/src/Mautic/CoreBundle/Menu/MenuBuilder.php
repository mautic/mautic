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
use Symfony\Component\DependencyInjection\ContainerAware;
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
    private $factory;
    private $bundles;
    private $matcher;
    private $securityContext;
    private $mauticSecurity;

    /**
     * @param FactoryInterface $factory
     * @param MatcherInterface $matcher
     * @param SecurityContext  $securityContext
     * @param CorePermissions  $permissions
     * @param array            $bundles
     */
    public function __construct(FactoryInterface $factory,
                                MatcherInterface $matcher,
                                SecurityContext $securityContext,
                                CorePermissions $permissions,
                                array $bundles
    )
    {
        $this->factory         = $factory;
        $this->matcher         = $matcher;
        $this->bundles         = $bundles;
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

            $menuItems = array();
            foreach ($this->bundles as $bundle) {
                //Load bundle menu.php if menu.php exists
                $parts = explode("\\", $bundle);
                $path  = __DIR__ . "/../../" . $parts[1] . "/Resources/config/menu.php";
                if (file_exists($path)) {
                    $items = include $path;

                    if ($parts[1] == "CoreBundle") {
                        //this means that core bundle must be loaded before other bundles as it has the root menu setup
                        $menuItems = $items;
                    } else {
                        $menuItems['children'] = array_merge($menuItems['children'], $items);
                    }
                }
            }

            $menu = $loader->load($menuItems);
        }
        return $menu;
    }

    /**
     * Converts navigation object into breadcrumbs
     *
     * @param Request $request
     */
    public function breadcrumbsMenu(Request $request) {
        $menu = $this->mainMenu($request);

        //check for overrideRoute in request from an ajax content request
        $forRouteUri  = $request->get("overrideRouteUri", "current");
        $forRouteName = $request->get("overrideRouteName", '');
        $current     = $this->getCurrentMenuItem($menu, $forRouteUri, $forRouteName);

        if (empty($current)) {
            //current is root
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