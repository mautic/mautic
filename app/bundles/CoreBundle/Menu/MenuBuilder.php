<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Menu;

use Knp\Menu\FactoryInterface;
use Knp\Menu\Loader\ArrayLoader;
use Knp\Menu\Matcher\MatcherInterface;
use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\MenuEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class MenuBuilder.
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
     * MenuBuilder constructor.
     *
     * @param FactoryInterface         $knpFactory
     * @param MatcherInterface         $matcher
     * @param EventDispatcherInterface $dispatcher
     * @param MenuHelper               $menuHelper
     */
    public function __construct(FactoryInterface $knpFactory, MatcherInterface $matcher, EventDispatcherInterface $dispatcher, MenuHelper $menuHelper)
    {
        $this->factory    = $knpFactory;
        $this->matcher    = $matcher;
        $this->dispatcher = $dispatcher;
        $this->menuHelper = $menuHelper;
    }

    /**
     * @param $name
     * @param $arguments
     *
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        $name = str_replace('Menu', '', $name);

        return $this->buildMenu($name);
    }

    /**
     * Used by breadcrumbs to determine active link.
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
                if ($forRouteUri == 'current' && $this->matcher->isCurrent($item)) {
                    //current match
                    return $item;
                } elseif ($forRouteUri != 'current' && $item->getUri() == $forRouteUri) {
                    //route uri match
                    return $item;
                } elseif (!empty($forRouteName) && $forRouteName == $item->getExtra('routeName')) {
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

    /**
     * @param $name
     *
     * @return mixed
     */
    private function buildMenu($name)
    {
        static $menus = [];

        if (!isset($menus[$name])) {
            $loader = new ArrayLoader($this->factory);

            //dispatch the MENU_BUILD event to retrieve bundle menu items
            $event = new MenuEvent($this->menuHelper, $name);
            $this->dispatcher->dispatch(CoreEvents::BUILD_MENU, $event);

            $menuItems    = $event->getMenuItems();
            $menus[$name] = $loader->load($menuItems);
        }

        return $menus[$name];
    }
}
