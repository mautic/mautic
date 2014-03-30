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
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
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

    /**
     * @param FactoryInterface $factory
     * @param MatcherInterface $matcher
     * @param                  $bundles
     */
    public function __construct(FactoryInterface $factory, MatcherInterface $matcher, $bundles)
    {
        $this->factory   = $factory;
        $this->matcher   = $matcher;
        $this->bundles   = $bundles;

    }

    /**
     * @param Request $request
     * @return \Knp\Menu\ItemInterface
     */
    public function mainMenu(Request $request)
    {
        static $menu;

        if (empty($menu)) {
            $menu = $this->factory->createItem('root', array('childrenAttributes' =>
                    array(
                        "class" => "side-panel-nav",
                        "role"  => "navigation"
                    )
            ));

            foreach ($this->bundles as $bundle) {
                //Load bundle menu.php if menu.php exists
                $parts = explode("\\", $bundle);
                $path  = __DIR__ . "/../../" . $parts[1] . "/Resources/config/menu.php";
                $items = array();
                if (file_exists($path)) {
                    //menu.php should just be $items = array("name" => array("options" => $options, "children" => array(...);
                    include_once $path;
                    $this->addMenuItems($menu, $items);
                }
            }
        }

        return $menu;
    }


    /**
     * @param        $menu
     * @param        $items
     * @param string $parent
     */
    private function addMenuItems(&$menu, &$items, $parent = "") {
        foreach ($items as $name => $item) {
            if (empty($parent)) {
                //parent item so add it at the parent level
                $menu->addChild($name, $item["options"]);
            } else {
                //child item so add it to the parent
                $menu[$parent]->addChild($name, $item["options"]);
            }

            if (!empty($item["children"])) {
                $this->addMenuItems($menu, $item["children"], $name);
            }
        }
    }

    /**
     * @param Request $request
     */
    public function breadcrumbsMenu(Request $request) {
        $menu = $this->mainMenu($request);
        $current = $this->getCurrentMenuItem($menu);

        if (empty($current)) {
            //current is root
            $current = $menu->getRoot();
        }
        return $current;
    }

    /**
     * @param $menu
     * @return null
     */
    public function getCurrentMenuItem($menu)
    {
        foreach ($menu as $item) {
            if ($this->matcher->isCurrent($item)) {
                return $item;
            }

            if ($item->getChildren() && $current_child = $this->getCurrentMenuItem($item)) {
                return $current_child;
            }
        }

        return null;
    }
}